<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DriverMessageRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;

/**
 * Executes the tool calls SyncAI's LLM asks for, against real company-scoped
 * data — this is what turns it from "reads a fixed context dump" into an
 * assistant that can actually go look things up. Every tool is read-only.
 */
final class AiToolExecutor
{
    public function __construct(
        private readonly ServiceRepository $services,
        private readonly UserRepository $users,
        private readonly ExpenseRepository $expenses,
        private readonly DriverMessageRepository $messages,
        private readonly ?int $companyId,
    ) {
    }

    public static function default(): self
    {
        return new self(
            ServiceRepository::default(),
            UserRepository::default(),
            ExpenseRepository::default(),
            DriverMessageRepository::default(),
            \App\Support\Session::companyId(),
        );
    }

    /** @return array<int,array<string,mixed>> OpenAI/Groq tool-definition schema. */
    public static function toolSchemas(): array
    {
        $dateProps = [
            'date_from' => ['type' => 'string', 'description' => 'Start date, format YYYY-MM-DD. Omit if not mentioned.'],
            'date_to'   => ['type' => 'string', 'description' => 'End date, format YYYY-MM-DD. Omit if not mentioned.'],
        ];

        return [
            self::fn('search_no_shows', 'Search reported no-show incidents (with photo evidence) by driver name, date range and/or route. Use this when asked to find a specific no-show, e.g. "the no-show from a few months ago" or "show me the photo of the no-show for X".', array_merge([
                'driver_name' => ['type' => 'string', 'description' => 'Partial driver name to filter by.'],
                'route'       => ['type' => 'string', 'description' => 'Partial pickup or dropoff location to filter by.'],
            ], $dateProps)),

            self::fn('search_vouchers', 'Search uploaded voucher photos by driver name, client name, date range and/or route. Use this when asked to find/show a voucher.', array_merge([
                'driver_name' => ['type' => 'string', 'description' => 'Partial driver name to filter by.'],
                'client_name' => ['type' => 'string', 'description' => 'Partial client name to filter by.'],
                'route'       => ['type' => 'string', 'description' => 'Partial pickup or dropoff location to filter by.'],
            ], $dateProps)),

            self::fn('search_rides', 'Search rides/services by driver, client, date range and/or route — general trip lookup, not limited to today.', array_merge([
                'driver_name' => ['type' => 'string'],
                'client_name' => ['type' => 'string'],
                'route'       => ['type' => 'string'],
            ], $dateProps)),

            self::fn('get_driver_stats', 'Get one specific driver\'s stats (trips, average rating, no-shows) for a given month.', [
                'driver_name' => ['type' => 'string', 'description' => 'Required. Partial name is fine.'],
                'month'       => ['type' => ['integer', 'string'], 'description' => 'Month number 1-12. Defaults to the current month if omitted.'],
                'year'        => ['type' => ['integer', 'string'], 'description' => 'Defaults to the current year if omitted.'],
            ], ['driver_name']),

            self::fn('search_chat_topics', 'Search the admin<->driver chat history (topics and messages) by keyword, optionally scoped to one driver. Use this for "what did we agree on in the chat about X".', [
                'keyword'     => ['type' => 'string', 'description' => 'Required search term.'],
                'driver_name' => ['type' => 'string', 'description' => 'Optional, narrows the search to one driver.'],
            ], ['keyword']),

            self::fn('get_expenses_summary', 'Get the total company expenses for a given month.', [
                'month' => ['type' => ['integer', 'string'], 'description' => 'Month number 1-12. Defaults to current month.'],
                'year'  => ['type' => ['integer', 'string'], 'description' => 'Defaults to current year.'],
            ]),

            self::fn('get_upcoming_bookings', 'List upcoming scheduled rides (from now onward).', [
                'limit' => ['type' => ['integer', 'string'], 'description' => 'Max number of results, default 10.'],
            ]),

            self::fn('get_driver_ranking', 'Compare ALL drivers against each other for one month — trips completed, average rating, and no-shows. Use this for any "who is my best/worst driver", "top drivers", "which driver has the most no-shows" type question. Returns every driver sorted by the requested metric, not just one.', [
                'metric' => ['type' => 'string', 'enum' => ['trips', 'rating', 'no_shows'], 'description' => 'What to rank by. "trips" = most rides done (higher better), "rating" = average client rating (higher better), "no_shows" = no-show count (lower better). Defaults to trips.'],
                'month'  => ['type' => ['integer', 'string'], 'description' => 'Month number 1-12. Defaults to current month.'],
                'year'   => ['type' => ['integer', 'string'], 'description' => 'Defaults to current year.'],
            ]),
        ];
    }

    private static function fn(string $name, string $description, array $properties, array $required = []): array
    {
        return [
            'type'     => 'function',
            'function' => [
                'name'        => $name,
                'description' => $description,
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => $properties,
                    'required'   => $required,
                ],
            ],
        ];
    }

    /**
     * @return array{result: mixed, attachments: array<int,array{type:string,url:string,caption:string}>}
     */
    public function execute(string $name, array $args): array
    {
        return match ($name) {
            'search_no_shows'     => $this->searchNoShows($args),
            'search_vouchers'     => $this->searchVouchers($args),
            'search_rides'        => $this->searchRides($args),
            'get_driver_stats'    => $this->getDriverStats($args),
            'search_chat_topics'  => $this->searchChatTopics($args),
            'get_expenses_summary'=> $this->getExpensesSummary($args),
            'get_upcoming_bookings' => $this->getUpcomingBookings($args),
            'get_driver_ranking'  => $this->getDriverRanking($args),
            default               => ['result' => ['error' => "Unknown tool: {$name}"], 'attachments' => []],
        };
    }

    /**
     * A "ride" attachment carries exactly the fields the existing ride-details
     * modal (openRideModal() in admin/dashboard.php) needs — clicking it in the
     * UI opens that same modal, no extra request.
     */
    private function rideAttachment(array $r): array
    {
        return [
            'type'  => 'ride',
            'ride'  => [
                'ID'                => (int) $r['ID'],
                'NomeCliente'       => $r['NomeCliente'] ?? null,
                'serviceStartTime'  => $r['serviceStartTime'] ?? null,
                'FlightNumber'      => $r['FlightNumber'] ?? null,
                'serviceStartPoint' => $r['serviceStartPoint'] ?? null,
                'serviceTargetPoint'=> $r['serviceTargetPoint'] ?? null,
                'paxADT'            => $r['paxADT'] ?? 0,
                'paxCHD'            => $r['paxCHD'] ?? 0,
            ],
            'label' => "#{$r['ID']} · {$r['serviceDate']} " . substr((string) $r['serviceStartTime'], 0, 5) . " · {$r['serviceStartPoint']} → {$r['serviceTargetPoint']}",
        ];
    }

    private function searchNoShows(array $a): array
    {
        $rows = $this->services->searchNoShows($a['driver_name'] ?? null, $a['date_from'] ?? null, $a['date_to'] ?? null, $a['route'] ?? null);
        $attachments = [];
        $result = array_map(function (array $r) use (&$attachments): array {
            if (!empty($r['noShowPhotoPath'])) {
                $attachments[] = ['type' => 'photo', 'url' => '/SRMT/public/' . ltrim((string) $r['noShowPhotoPath'], '/'), 'caption' => "No-show #{$r['ID']} · {$r['serviceDate']} · {$r['driverName']}"];
            }
            $attachments[] = $this->rideAttachment($r);
            return [
                'id' => (int) $r['ID'], 'date' => $r['serviceDate'], 'time' => substr((string) $r['serviceStartTime'], 0, 5),
                'driver' => $r['driverName'], 'route' => $r['serviceStartPoint'] . ' → ' . $r['serviceTargetPoint'],
                'has_photo' => !empty($r['noShowPhotoPath']), 'has_report' => !empty($r['noShowReportPath']),
            ];
        }, $rows);
        return ['result' => $result === [] ? ['message' => 'No matching no-shows found.'] : $result, 'attachments' => $attachments];
    }

    private function searchVouchers(array $a): array
    {
        $rows = $this->services->searchVouchers($a['driver_name'] ?? null, $a['date_from'] ?? null, $a['date_to'] ?? null, $a['route'] ?? null, $a['client_name'] ?? null);
        $attachments = [];
        $result = array_map(function (array $r) use (&$attachments): array {
            if (!empty($r['voucher_photo'])) {
                $attachments[] = ['type' => 'photo', 'url' => '/SRMT/public/' . ltrim((string) $r['voucher_photo'], '/'), 'caption' => "Voucher #{$r['ID']} · {$r['serviceDate']} · {$r['NomeCliente']}"];
            }
            $attachments[] = $this->rideAttachment($r);
            return [
                'id' => (int) $r['ID'], 'date' => $r['serviceDate'], 'client' => $r['NomeCliente'],
                'driver' => $r['driverName'], 'route' => $r['serviceStartPoint'] . ' → ' . $r['serviceTargetPoint'],
            ];
        }, $rows);
        return ['result' => $result === [] ? ['message' => 'No matching vouchers found.'] : $result, 'attachments' => $attachments];
    }

    private function searchRides(array $a): array
    {
        $rows = $this->services->searchRides($a['driver_name'] ?? null, $a['client_name'] ?? null, $a['date_from'] ?? null, $a['date_to'] ?? null, $a['route'] ?? null);
        $attachments = array_map(fn(array $r) => $this->rideAttachment($r), $rows);
        $result = array_map(static fn(array $r): array => [
            'id' => (int) $r['ID'], 'date' => $r['serviceDate'], 'time' => substr((string) $r['serviceStartTime'], 0, 5),
            'client' => $r['NomeCliente'], 'driver' => $r['driverName'],
            'route' => $r['serviceStartPoint'] . ' → ' . $r['serviceTargetPoint'],
            'no_show' => (bool) $r['noShowStatus'], 'price' => $r['total_price'],
        ], $rows);
        return ['result' => $result === [] ? ['message' => 'No matching rides found.'] : $result, 'attachments' => $attachments];
    }

    private function getDriverStats(array $a): array
    {
        $name  = (string) ($a['driver_name'] ?? '');
        $month = (int) ($a['month'] ?? date('m'));
        $year  = (int) ($a['year'] ?? date('Y'));
        if ($name === '') {
            return ['result' => ['error' => 'driver_name is required'], 'attachments' => []];
        }
        $stats = $this->services->statsForDriverByName($name, $month, $year);
        if ($stats === null || $stats['id'] === null) {
            return ['result' => ['message' => "No driver found matching \"{$name}\"."], 'attachments' => []];
        }
        return ['result' => [
            'driver'          => $stats['name'],
            'month'           => $month,
            'year'            => $year,
            'trips'           => (int) $stats['trips_month'],
            'avg_rating'      => $stats['avg_rating'] !== null ? round((float) $stats['avg_rating'], 2) : null,
            'no_shows'        => (int) $stats['no_shows_month'],
        ], 'attachments' => []];
    }

    private function searchChatTopics(array $a): array
    {
        $keyword = (string) ($a['keyword'] ?? '');
        if ($keyword === '' || $this->companyId === null) {
            return ['result' => ['error' => 'keyword is required'], 'attachments' => []];
        }
        $driverId = null;
        if (!empty($a['driver_name'])) {
            $driver = $this->users->findByNameLike((string) $a['driver_name']);
            if ($driver === null) {
                return ['result' => ['message' => "No driver found matching \"{$a['driver_name']}\"."], 'attachments' => []];
            }
            $driverId = $driver->id;
        }
        $rows = $this->messages->searchForAdmin($this->companyId, $keyword, $driverId);
        $result = array_map(static fn(array $r): array => [
            'driver' => $r['driver_name'], 'topic' => $r['is_general'] ? 'General' : ($r['topic_title'] ?? 'Untitled'),
            'message' => $r['message'], 'timestamp' => $r['timestamp'],
        ], $rows);
        return ['result' => $result === [] ? ['message' => 'No matching chat messages found.'] : $result, 'attachments' => []];
    }

    private function getExpensesSummary(array $a): array
    {
        $month = (int) ($a['month'] ?? date('m'));
        $year  = (int) ($a['year'] ?? date('Y'));
        $total = $this->expenses->totalForMonth(sprintf('%04d-%02d', $year, $month));
        return ['result' => ['month' => $month, 'year' => $year, 'total' => $total . '€'], 'attachments' => []];
    }

    private function getUpcomingBookings(array $a): array
    {
        $limit = (int) ($a['limit'] ?? 10);
        $rows  = $this->services->upcoming($limit);
        $attachments = array_map(fn(array $r) => $this->rideAttachment($r), $rows);
        $result = array_map(static fn(array $r): array => [
            'id' => (int) $r['ID'], 'date' => $r['serviceDate'], 'time' => substr((string) $r['serviceStartTime'], 0, 5), 'client' => $r['NomeCliente'],
        ], $rows);
        return ['result' => $result === [] ? ['message' => 'No upcoming bookings.'] : $result, 'attachments' => $attachments];
    }

    private function getDriverRanking(array $a): array
    {
        $metric = in_array($a['metric'] ?? 'trips', ['trips', 'rating', 'no_shows'], true) ? $a['metric'] : 'trips';
        $month  = (int) ($a['month'] ?? date('m'));
        $year   = (int) ($a['year'] ?? date('Y'));

        $rows = $this->services->driverRankingForMonth($month, $year);
        $ranked = array_map(static fn(array $r): array => [
            'driver'   => $r['name'],
            'trips'    => (int) $r['trips'],
            'rating'   => $r['rating'] !== null ? round((float) $r['rating'], 2) : null,
            'no_shows' => (int) $r['no_shows'],
        ], $rows);

        usort($ranked, static function (array $a, array $b) use ($metric): int {
            // rating: nulls (no ratings yet) sort last regardless of direction.
            if ($metric === 'rating') {
                if ($a['rating'] === null) return 1;
                if ($b['rating'] === null) return -1;
                return $b['rating'] <=> $a['rating'];
            }
            // no_shows: lower is better, so ascending; trips: higher is better, descending.
            return $metric === 'no_shows' ? $a['no_shows'] <=> $b['no_shows'] : $b['trips'] <=> $a['trips'];
        });

        return ['result' => $ranked === [] ? ['message' => 'No drivers found.'] : ['metric' => $metric, 'month' => $month, 'year' => $year, 'ranking' => $ranked], 'attachments' => []];
    }
}
