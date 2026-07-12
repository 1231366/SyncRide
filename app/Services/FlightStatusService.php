<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Env;

/**
 * Live flight status (delays / ETA / landed) for airport-pickup rides.
 *
 * Provider: AeroDataBox via RapidAPI (free tier). Configure in .env:
 *   FLIGHT_API_KEY=<your RapidAPI key>
 *   FLIGHT_API_DAILY_BUDGET=60          (optional, upstream calls/day cap)
 *
 * Designed to degrade gracefully: no key, upstream down, flight not found or
 * budget exhausted all yield ['found' => false] — the UI simply shows nothing.
 *
 * Caching (storage/cache/flights/):
 *   - live states cached 10 min, final states (landed/cancelled) 60 min;
 *   - a daily upstream-call counter enforces the free-tier budget; when it
 *     is exhausted we keep serving the last cached answer (stale-if-needed).
 */
final class FlightStatusService
{
    private const TTL_LIVE     = 600;
    private const TTL_FINAL    = 3600;
    private const TTL_NEGATIVE = 900;

    /** Final states — the upstream answer can no longer change. */
    private const FINAL_STATUSES = ['landed', 'cancelled', 'diverted'];

    public function __construct(
        private readonly string $apiKey,
        private readonly int $dailyBudget = 60,
        private readonly ?string $cacheDir = null,
    ) {
    }

    public static function default(): self
    {
        return new self(
            (string) Env::get('FLIGHT_API_KEY', ''),
            max(1, (int) Env::get('FLIGHT_API_DAILY_BUDGET', 60)),
        );
    }

    /**
     * @param string $flightNumber e.g. "TP 1934", "u2 7635", "FR4587"
     * @param string $dateLocal    "YYYY-MM-DD" (local date of arrival)
     *
     * @return array{found:bool, flight?:string, status?:string, delay_min?:int|null,
     *               sched_arr?:string|null, est_arr?:string|null, airport?:string|null}
     */
    public function get(string $flightNumber, string $dateLocal): array
    {
        $flight = strtoupper(preg_replace('/\s+/', '', $flightNumber) ?? '');
        if ($flight === '' || !preg_match('/^[A-Z0-9]{2,3}\d{1,4}[A-Z]?$/', $flight)) {
            return ['found' => false];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateLocal)) {
            return ['found' => false];
        }
        if ($this->apiKey === '') {
            return ['found' => false];
        }

        $cacheFile = $this->cachePath("{$flight}_{$dateLocal}.json");
        $cached    = $this->readCache($cacheFile);
        if ($cached !== null && $cached['_fresh']) {
            unset($cached['_fresh'], $cached['_cached_at']);
            return $cached;
        }

        if (!$this->consumeBudget()) {
            // Budget exhausted — serve yesterday's answer rather than nothing.
            if ($cached !== null) {
                unset($cached['_fresh'], $cached['_cached_at']);
                return $cached;
            }
            return ['found' => false];
        }

        $legs = $this->fetchUpstream($flight, $dateLocal);
        if ($legs === null) {
            // Upstream error: keep any stale cache alive, negative-cache otherwise.
            if ($cached !== null) {
                unset($cached['_fresh'], $cached['_cached_at']);
                return $cached;
            }
            $result = ['found' => false];
            $this->writeCache($cacheFile, $result);
            return $result;
        }

        $result = $this->mapLegs($legs, $flight);
        $this->writeCache($cacheFile, $result);
        return $result;
    }

    // ── Upstream ──────────────────────────────────────────────────────────

    /** @return array<int,array<string,mixed>>|null null = transport/HTTP error */
    private function fetchUpstream(string $flight, string $dateLocal): ?array
    {
        $url = 'https://aerodatabox.p.rapidapi.com/flights/number/'
             . rawurlencode($flight) . '/' . $dateLocal
             . '?withAircraftImage=false&withLocation=false';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'X-RapidAPI-Key: '  . $this->apiKey,
                'X-RapidAPI-Host: aerodatabox.p.rapidapi.com',
                'Accept: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $code >= 500 || $code === 429) {
            return null; // transient — let stale cache survive
        }
        if ($code === 404 || $code === 204) {
            return []; // definitive: flight not found on that date
        }
        if ($code !== 200) {
            return null;
        }
        $data = json_decode((string) $body, true);
        return is_array($data) ? $data : null;
    }

    // ── Mapping ───────────────────────────────────────────────────────────

    /** @param array<int,array<string,mixed>> $legs */
    private function mapLegs(array $legs, string $flight): array
    {
        if ($legs === []) {
            return ['found' => false];
        }

        // Multi-leg flights: prefer the leg arriving at a Portuguese airport
        // (SyncRide pickups happen there); otherwise take the last leg.
        $ptAirports = ['LIS', 'OPO', 'FAO', 'FNC', 'PDL', 'PXO', 'TER', 'HOR'];
        $leg = null;
        foreach ($legs as $candidate) {
            $iata = strtoupper((string) ($candidate['arrival']['airport']['iata'] ?? ''));
            if (in_array($iata, $ptAirports, true)) {
                $leg = $candidate;
                break;
            }
        }
        $leg ??= end($legs) ?: null;
        if (!is_array($leg)) {
            return ['found' => false];
        }

        $arr   = (array) ($leg['arrival'] ?? []);
        $sched = $this->pickTime($arr, ['scheduledTime', 'scheduledTimeLocal']);
        $est   = $this->pickTime($arr, ['revisedTime', 'actualTimeLocal', 'predictedTime', 'estimatedTimeLocal']);

        $delayMin = null;
        if ($sched !== null && $est !== null) {
            $delayMin = (int) round(($est->getTimestamp() - $sched->getTimestamp()) / 60);
        }

        $rawStatus = strtolower((string) ($leg['status'] ?? ''));
        $status = match (true) {
            str_contains($rawStatus, 'cancel')                  => 'cancelled',
            str_contains($rawStatus, 'divert')                  => 'diverted',
            str_contains($rawStatus, 'arrived')                 => 'landed',
            str_contains($rawStatus, 'delayed')                 => 'delayed',
            $delayMin !== null && $delayMin >= 10               => 'delayed',
            $sched !== null                                     => 'ontime',
            default                                             => 'unknown',
        };

        return [
            'found'     => true,
            'flight'    => $flight,
            'status'    => $status,
            'delay_min' => ($delayMin !== null && $delayMin >= 5) ? $delayMin : null,
            'sched_arr' => $sched?->format('H:i'),
            'est_arr'   => $est?->format('H:i'),
            'airport'   => strtoupper((string) ($arr['airport']['iata'] ?? '')) ?: null,
        ];
    }

    /**
     * AeroDataBox has shipped two shapes over time:
     *   new:  arrival.scheduledTime = {utc: "...", local: "2026-07-01 11:20+01:00"}
     *   old:  arrival.scheduledTimeLocal = "2026-07-01 11:20+01:00"
     * Accept both; always return the airport-local wall-clock time.
     *
     * @param array<string,mixed> $arr
     * @param string[] $keys probed in order
     */
    private function pickTime(array $arr, array $keys): ?\DateTimeImmutable
    {
        foreach ($keys as $key) {
            $value = $arr[$key] ?? null;
            if (is_array($value)) {
                $value = $value['local'] ?? $value['utc'] ?? null;
            }
            if (!is_string($value) || trim($value) === '') {
                continue;
            }
            try {
                return new \DateTimeImmutable(trim($value));
            } catch (\Throwable) {
                continue;
            }
        }
        return null;
    }

    // ── Cache + budget ────────────────────────────────────────────────────

    private function cachePath(string $file): string
    {
        $dir = $this->cacheDir ?? dirname(__DIR__, 2) . '/storage/cache/flights';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . '/' . $file;
    }

    /** @return array<string,mixed>|null with `_fresh` flag, null when absent */
    private function readCache(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || !isset($data['_cached_at'])) {
            return null;
        }
        $ttl = self::TTL_NEGATIVE;
        if (($data['found'] ?? false) === true) {
            $ttl = in_array($data['status'] ?? '', self::FINAL_STATUSES, true)
                ? self::TTL_FINAL
                : self::TTL_LIVE;
        }
        $data['_fresh'] = (time() - (int) $data['_cached_at']) < $ttl;
        return $data;
    }

    private function writeCache(string $file, array $result): void
    {
        $result['_cached_at'] = time();
        @file_put_contents($file, json_encode($result), LOCK_EX);
    }

    /** True when today's upstream budget still allows one more call. */
    private function consumeBudget(): bool
    {
        $file  = $this->cachePath('budget_' . date('Y-m-d') . '.cnt');
        $count = is_file($file) ? (int) file_get_contents($file) : 0;
        if ($count >= $this->dailyBudget) {
            return false;
        }
        @file_put_contents($file, (string) ($count + 1), LOCK_EX);
        return true;
    }
}
