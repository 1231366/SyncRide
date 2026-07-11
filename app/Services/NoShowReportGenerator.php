<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LogRepository;
use App\Support\Database;

/**
 * Generates a no-show incident PDF report using FPDF.
 * Returns the relative DB path to the saved file.
 *
 * The report is localised to the tenant's language (resources/lang/<lang>.php).
 * FPDF's core fonts are cp1252, so every string is converted from UTF-8 via
 * enc() before being drawn — otherwise accented characters would be mojibake.
 */
final class NoShowReportGenerator
{
    private const FPDF = __DIR__ . '/../../vendor/fpdf/fpdf.php';

    // Brand palette
    private const BG = [248, 250, 252];
    private const BLUE = [37, 99, 235];
    private const BLUE_LIGHT = [239, 246, 255];
    private const RED = [220, 38, 38];
    private const BORDER = [226, 232, 240];
    private const TEXT1 = [15, 23, 42];
    private const TEXT2 = [71, 85, 105];
    private const TEXT3 = [148, 163, 184];

    public function generate(
        int     $tripId,
        array   $tripData,
        string  $photoServerPath,
        ?string $lat,
        ?string $lng,
        ?string $noShowAt = null,
        ?string $lang = null,
    ): string {
        require_once self::FPDF;

        date_default_timezone_set('Europe/Lisbon');

        // ── Localisation ──────────────────────────────────────────
        $lang     = $lang ?: ($_SESSION['admin_lang'] ?? 'en');
        $langFile = dirname(__DIR__, 2) . '/resources/lang/' . preg_replace('/[^a-z]/i', '', (string) $lang) . '.php';
        $S        = is_file($langFile) ? (require $langFile) : [];
        $raw      = static fn(string $k): string => (string) ($S[$k] ?? $k);          // UTF-8
        $t        = fn(string $k): string => $this->enc($raw($k));                    // cp1252, ready to draw

        $now         = date('d/m/Y H:i');
        $companyName = (string) ($tripData['company_name']       ?? 'SyncRide');
        $driverName  = (string) ($tripData['driver_name']        ?? 'N/A');
        $clientName  = mb_strtoupper((string) ($tripData['NomeCliente'] ?? 'N/A'), 'UTF-8');
        $origin      = (string) ($tripData['serviceStartPoint']  ?? '');
        $destination = (string) ($tripData['serviceTargetPoint'] ?? '');
        $date        = !empty($tripData['serviceDate'])
            ? (new \DateTime($tripData['serviceDate']))->format('d/m/Y')
            : date('d/m/Y');
        $time        = !empty($tripData['serviceStartTime'])
            ? substr((string) $tripData['serviceStartTime'], 0, 5)
            : '';

        // ── Waiting-time evidence ─────────────────────────────────
        // Driver arrival comes from the live status flow (ts_arrived_pickup);
        // the no-show moment is passed in by the controller (and persisted).
        $toHm = static function ($raw): ?string {
            if (empty($raw) || str_starts_with((string) $raw, '0000')) {
                return null;
            }
            try { return (new \DateTime((string) $raw))->format('H:i'); }
            catch (\Throwable) { return null; }
        };
        $arrivedRaw  = $tripData['ts_arrived_pickup'] ?? null;
        $arrivedTime = $toHm($arrivedRaw);
        $noShowTime  = $toHm($noShowAt) ?? date('H:i');

        $waitingMin = null;
        if ($arrivedTime !== null && !empty($noShowAt)) {
            try {
                $diff = (new \DateTime((string) $noShowAt))->getTimestamp()
                      - (new \DateTime((string) $arrivedRaw))->getTimestamp();
                if ($diff >= 0) {
                    $waitingMin = (int) round($diff / 60);
                }
            } catch (\Throwable) { /* leave null */ }
        }

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        // ── PAGE BACKGROUND ───────────────────────────────────────
        $pdf->SetFillColor(...self::BG);
        $pdf->Rect(0, 0, 210, 297, 'F');

        // ── HEADER BAR ────────────────────────────────────────────
        $pdf->SetFillColor(...self::BLUE);
        $pdf->Rect(0, 0, 210, 40, 'F');

        // Small decorative accent strip (darker blue)
        $pdf->SetFillColor(29, 78, 216);
        $pdf->Rect(0, 35, 210, 5, 'F');

        // Logo — white SyncRide wordmark on the blue header
        $logo = dirname(__DIR__, 2) . '/public/assets/images/icons/Syncridewhite.png';
        if (is_file($logo)) {
            $pdf->Image($logo, 15, 11, 34); // height auto from 2.36 ratio (~14mm)
        } else {
            $pdf->SetXY(15, 10);
            $pdf->SetFont('Helvetica', 'B', 20);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(0, 10, 'SyncRide', 0, 0, 'L');
        }

        // Right — report label
        $pdf->SetXY(105, 9);
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(90, 8, $t('noshows.report.title'), 0, 0, 'R');

        $pdf->SetXY(105, 18);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(147, 197, 253);
        $pdf->Cell(90, 6, $t('noshows.report.ride') . ' #' . $tripId, 0, 0, 'R');

        $pdf->SetXY(105, 26);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(186, 230, 253); // blue-200
        $pdf->Cell(90, 5, $t('noshows.report.generated') . ': ' . $now, 0, 0, 'R');

        // ── COMPANY BAND ──────────────────────────────────────────
        $pdf->SetFillColor(...self::BLUE_LIGHT);
        $pdf->Rect(0, 40, 210, 16, 'F');

        $pdf->SetXY(15, 44);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(...self::BLUE);
        $pdf->Cell(130, 7, $this->enc($companyName), 0, 0, 'L');

        // NO-SHOW pill
        $pdf->SetFillColor(...self::RED);
        $pdf->Rect(158, 44, 38, 8, 'F');
        $pdf->SetXY(158, 44);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(38, 8, ' ' . $t('noshows.report.badge') . ' ', 0, 0, 'C');

        // ── SECTION: SERVICE DETAILS ──────────────────────────────
        $y = 62;

        $pdf->SetDrawColor(...self::BORDER);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(15, $y, 180, 52, 'FD');

        // Section title
        $pdf->SetXY(15, $y + 4);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->Cell(180, 4, '  ' . $t('noshows.report.service_details'), 0, 1, 'L');

        $pdf->SetDrawColor(...self::BORDER);
        $pdf->Line(15, $y + 10, 195, $y + 10);

        $y += 13;

        // Two-column details (labels passed UTF-8; detailCell upper-cases + encodes)
        $lx = 20; $rx = 115; $colW = 85;
        foreach ([
            [[$raw('noshows.report.driver'), $driverName],      [$raw('noshows.report.datetime'), $date . '  ' . $time]],
            [[$raw('noshows.report.client'), $clientName],      [$raw('noshows.report.service_id'), '#' . $tripId]],
            [[$raw('noshows.report.origin'), $origin],          null],
            [[$raw('noshows.report.destination'), $destination], null],
        ] as $row) {
            [$label, $value] = $row[0];
            $this->detailCell($pdf, $lx, $y, $label, $value, $colW);
            if ($row[1] !== null) {
                [$rlabel, $rvalue] = $row[1];
                $this->detailCell($pdf, $rx, $y, $rlabel, $rvalue, $colW);
            }
            $y += 8;
        }

        if ($lat && $lng) {
            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($lat . ',' . $lng);
            $this->detailCell($pdf, $lx, $y, $raw('noshows.report.gps'), $lat . ', ' . $lng, 160, $mapsUrl);
            $y += 8;
        }

        // ── SECTION: WAITING-TIME EVIDENCE ────────────────────────
        $ty = 118;
        $tH = 32;
        $pdf->SetDrawColor(...self::BORDER);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(15, $ty, 180, $tH, 'FD');

        $pdf->SetXY(15, $ty + 4);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->Cell(180, 4, '  ' . $t('noshows.report.waiting_evidence'), 0, 1, 'L');
        $pdf->Line(15, $ty + 10, 195, $ty + 10);

        $minLabel = $raw('noshows.report.minutes');
        $stats = [
            [$t('noshows.report.scheduled_pickup'), $time !== '' ? $time : '--:--', self::TEXT1],
            [$t('noshows.report.driver_arrived'),   $arrivedTime ?? '--:--',        self::TEXT2],
            [$t('noshows.report.noshow_declared'),  $noShowTime,                    self::TEXT1],
            [$t('noshows.report.waiting_time'),     $waitingMin !== null ? $waitingMin . ' ' . $minLabel : '--', self::RED],
        ];
        $cw = 180 / 4;
        $cx = 15;
        foreach ($stats as $i => [$label, $value, $colour]) {
            $pdf->SetXY($cx, $ty + 14);
            $pdf->SetFont('Helvetica', 'B', 6);
            $pdf->SetTextColor(...self::TEXT3);
            $pdf->Cell($cw, 4, $label, 0, 0, 'C');

            $pdf->SetXY($cx, $ty + 19);
            $pdf->SetFont('Helvetica', 'B', 13);
            $pdf->SetTextColor(...$colour);
            $pdf->Cell($cw, 8, $this->enc($value), 0, 0, 'C');

            if ($i < 3) {
                $pdf->SetDrawColor(...self::BORDER);
                $pdf->Line($cx + $cw, $ty + 12, $cx + $cw, $ty + $tH - 3);
            }
            $cx += $cw;
        }

        // ── SECTION: GPS TRAIL (one coordinate per status step) ───
        // The single GPS point above is where the no-show was declared; this
        // section lists the whole live-status trail so every step is evidenced.
        $steps = $this->statusTrail($tripId);
        if ($lat && $lng) {
            // Include the no-show declaration itself as the final step.
            $steps[] = ['time' => $noShowTime, 'label' => $raw('noshows.report.noshow_declared'), 'lat' => $lat, 'lng' => $lng];
        }

        $photoY = 154; // default when there is no trail to show
        if ($steps !== []) {
            $rowH   = 6;
            $trailY = 154;
            $trailH = 12 + count($steps) * $rowH + 3;

            $pdf->SetDrawColor(...self::BORDER);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect(15, $trailY, 180, $trailH, 'FD');

            $pdf->SetXY(15, $trailY + 4);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetTextColor(...self::TEXT3);
            $pdf->Cell(180, 4, '  ' . $t('noshows.report.location_trail'), 0, 1, 'L');
            $pdf->Line(15, $trailY + 10, 195, $trailY + 10);

            $ry = $trailY + 12;
            foreach ($steps as $s) {
                $pdf->SetXY(20, $ry);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetTextColor(...self::TEXT2);
                $pdf->Cell(16, 5, $this->enc((string) $s['time']), 0, 0, 'L');

                $pdf->SetXY(38, $ry);
                $pdf->SetFont('Helvetica', '', 8.5);
                $pdf->SetTextColor(...self::TEXT1);
                $pdf->Cell(78, 5, $this->enc(mb_strtoupper((string) $s['label'], 'UTF-8')), 0, 0, 'L');

                $pdf->SetXY(118, $ry);
                if (!empty($s['lat']) && !empty($s['lng'])) {
                    $coords = $s['lat'] . ', ' . $s['lng'];
                    $url    = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($s['lat'] . ',' . $s['lng']);
                    $pdf->SetFont('Helvetica', 'U', 8);
                    $pdf->SetTextColor(...self::BLUE);
                    $pdf->Cell(76, 5, $this->enc($coords), 0, 0, 'L', false, $url);
                } else {
                    $pdf->SetFont('Helvetica', 'I', 8);
                    $pdf->SetTextColor(...self::TEXT3);
                    $pdf->Cell(76, 5, $t('noshows.report.no_gps'), 0, 0, 'L');
                }
                $ry += $rowH;
            }

            $photoY = $trailY + $trailH + 6;
        }

        // ── SECTION: PHOTO EVIDENCE ───────────────────────────────
        $y = $photoY;

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(...self::BORDER);

        // Footer is at y=262; reserve room for the section chrome + gap.
        $maxPhotoH = 262 - $y - 22 - 6; // available height for the image itself
        $imgExists = file_exists($photoServerPath);
        if ($imgExists) {
            // Fit the photo INSIDE a 160 x maxPhotoH box, preserving aspect ratio.
            // (Previously width was fixed at 160 while height was clamped, which
            //  stretched portrait photos.)
            [$iw, $ih] = getimagesize($photoServerPath) ?: [0, 0];
            if ($iw > 0 && $ih > 0) {
                $scale  = min(160.0 / $iw, (float) $maxPhotoH / $ih);
                $photoW = $iw * $scale;
                $photoH = $ih * $scale;
            } else {
                $photoW = 160.0;
                $photoH = min(40.0, (float) $maxPhotoH);
            }
        } else {
            $photoW = 160.0;
            $photoH = 40.0;
        }
        $sectionH = $photoH + 22;

        $pdf->Rect(15, $y, 180, $sectionH, 'FD');

        $pdf->SetXY(15, $y + 4);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->Cell(180, 4, '  ' . $t('noshows.report.photo_evidence'), 0, 1, 'L');

        $pdf->Line(15, $y + 10, 195, $y + 10);

        if ($imgExists) {
            $imgX = (210 - $photoW) / 2; // centred, whatever the aspect ratio
            $imgY = $y + 13;

            // Shadow/border behind photo
            $pdf->SetFillColor(...self::BORDER);
            $pdf->Rect($imgX - 1, $imgY - 1, $photoW + 2, $photoH + 2, 'F');
            $pdf->Image($photoServerPath, $imgX, $imgY, $photoW, $photoH);
        } else {
            $pdf->SetXY(15, $y + 20);
            $pdf->SetFont('Helvetica', 'I', 9);
            $pdf->SetTextColor(...self::TEXT3);
            $pdf->Cell(180, 8, $t('noshows.report.photo_unavailable'), 0, 0, 'C');
        }

        $y += $sectionH + 10;

        // ── FOOTER (fixed at bottom of page 1) ────────────────────
        $fy = 262;
        $pdf->SetDrawColor(...self::BORDER);
        $pdf->Line(15, $fy, 195, $fy);

        $pdf->SetXY(15, $fy + 4);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(...self::TEXT2);
        $pdf->Cell(130, 4, $t('noshows.report.report_id') . ': #' . $tripId, 0, 0, 'L');
        $pdf->Cell(50, 4, $t('noshows.report.powered_by'), 0, 0, 'R');

        $pdf->SetXY(15, $fy + 10);
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->MultiCell(180, 3.5, $t('noshows.report.disclaimer'), 0, 'L');

        // ── SAVE ──────────────────────────────────────────────────
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/no_shows/';
        $fileName  = 'noshow_report_' . $tripId . '_' . time() . '.pdf';
        $pdf->Output($uploadDir . $fileName, 'F');

        return 'uploads/no_shows/' . $fileName;
    }

    /** Convert a UTF-8 string to cp1252 so FPDF core fonts render accents correctly. */
    private function enc(string $s): string
    {
        $out = iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        return $out !== false ? $out : $s;
    }

    private function detailCell(\FPDF $pdf, float $x, float $y, string $label, string $value, float $w, ?string $link = null): void
    {
        $pdf->SetXY($x, $y);
        $pdf->SetFont('Helvetica', 'B', 6.5);
        $pdf->SetTextColor(...self::TEXT3);
        $pdf->Cell(22, 5, $this->enc(mb_strtoupper($label, 'UTF-8')), 0, 0, 'L');

        // A link (e.g. GPS → Google Maps) is drawn blue + underlined to signal it's clickable.
        if ($link !== null) {
            $pdf->SetFont('Helvetica', 'U', 9);
            $pdf->SetTextColor(...self::BLUE);
            $pdf->Cell($w - 22, 5, $this->enc($value), 0, 0, 'L', false, $link);
        } else {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(...self::TEXT1);
            $pdf->Cell($w - 22, 5, $this->enc($value), 0, 0, 'L');
        }
    }

    /**
     * The live-status trail for a ride: one entry per status change, in order,
     * each with the GPS fix captured at that moment (may be null for old rides).
     * Consecutive duplicates are collapsed so repeated pings don't clutter it.
     *
     * @return list<array{time:string,label:string,lat:?string,lng:?string}>
     */
    private function statusTrail(int $tripId): array
    {
        try {
            $logs = (new LogRepository(Database::connection()))->forRide($tripId);
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        $lastLabel = null;
        foreach ($logs as $l) {
            $action = (string) $l['Action'];
            // Only status-change entries (English "status changed to" or the
            // legacy Portuguese "Estado alterado para").
            if (stripos($action, 'status changed to') === false
                && stripos($action, 'Estado alterado') === false) {
                continue;
            }
            $label = preg_replace('/^.*#' . $tripId . ':\s*/', '', $action);
            $label = preg_replace('/^(status changed to|Estado alterado para)\s*/i', '', (string) $label);
            $label = trim((string) $label);
            if ($label === '' || $label === $lastLabel) {
                continue;
            }
            $lastLabel = $label;
            $out[] = [
                'time'  => date('H:i', strtotime((string) $l['date'])),
                'label' => $label,
                'lat'   => isset($l['lat']) ? (string) $l['lat'] : null,
                'lng'   => isset($l['lng']) ? (string) $l['lng'] : null,
            ];
        }
        return $out;
    }
}
