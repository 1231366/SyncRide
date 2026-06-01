<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\Support\Session;

final class ScheduleBoardController extends BaseController
{
    private ServiceRepository $services;
    private UserRepository    $users;

    public function __construct()
    {
        $this->services = ServiceRepository::default();
        $this->users    = UserRepository::default();
    }

    /** GET /admin/schedule-board.php */
    public function index(): void
    {
        $this->view('admin.schedule-board.index', [
            'drivers' => $this->users->byRole(User::ROLE_DRIVER),
        ]);
    }

    /** GET /admin/api-schedule-board.php?start=X&end=Y — FullCalendar events feed. */
    public function events(): never
    {
        $from = substr(trim((string) ($_GET['start'] ?? '')), 0, 10);
        $to   = substr(trim((string) ($_GET['end']   ?? '')), 0, 10);

        if (!$from || !$to) {
            $this->json([]);
        }

        $rides = $this->services->getScheduledRides($from, $to);

        $palette      = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#14b8a6'];
        $driverColors = [];
        $events       = [];

        foreach ($rides as $r) {
            $driverId = $r['driver_id'] ? (int) $r['driver_id'] : null;
            if ($driverId !== null && !isset($driverColors[$driverId])) {
                $driverColors[$driverId] = $palette[count($driverColors) % count($palette)];
            }
            $color = $driverId !== null ? $driverColors[$driverId] : '#64748b';

            $startDt = $r['serviceDate'] . 'T' . substr((string) $r['serviceStartTime'], 0, 5);
            $endDt   = $r['serviceDate'] . 'T' . date('H:i', strtotime($r['serviceStartTime']) + 3600);

            $pax   = (int) $r['paxADT'] + (int) $r['paxCHD'] + (int) $r['paxBBY'];
            $title = ($r['NomeCliente'] ?: '—') . ' · ' . $pax . ' pax';

            $events[] = [
                'id'              => (int) $r['ID'],
                'title'           => $title,
                'start'           => $startDt,
                'end'             => $endDt,
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'extendedProps'   => [
                    'driver_id'   => $driverId,
                    'driver_name' => $r['driver_name'] ?? null,
                    'pickup'      => $r['serviceStartPoint']  ?? '',
                    'dropoff'     => $r['serviceTargetPoint'] ?? '',
                    'flight'      => $r['FlightNumber']       ?? '',
                    'pax_adt'     => (int) $r['paxADT'],
                    'pax_chd'     => (int) $r['paxCHD'],
                    'pax_bby'     => (int) $r['paxBBY'],
                    'type'        => (int) $r['serviceType'],
                ],
            ];
        }

        $this->json($events);
    }

    /** GET /admin/api-schedule-staged.php — unassigned upcoming trips. */
    public function staged(): never
    {
        $rides = $this->services->getStagedRides();
        $this->json(array_map(static fn(array $r): array => [
            'id'      => (int) $r['ID'],
            'date'    => $r['serviceDate'],
            'time'    => substr((string) $r['serviceStartTime'], 0, 5),
            'client'  => (string) ($r['NomeCliente']        ?? '—'),
            'pickup'  => (string) ($r['serviceStartPoint']  ?? ''),
            'dropoff' => (string) ($r['serviceTargetPoint'] ?? ''),
            'flight'  => (string) ($r['FlightNumber']       ?? ''),
            'pax'     => (int) $r['paxADT'] + (int) $r['paxCHD'] + (int) $r['paxBBY'],
            'type'    => (int) $r['serviceType'],
        ], $rides));
    }

    /** POST /admin/schedule-board-update.php — reschedule and/or assign driver. */
    public function update(): never
    {
        $this->requirePost();

        $rideId   = (int)    $this->input('ride_id',   0);
        $date     = (string) $this->input('date',      '');
        $time     = (string) $this->input('time',      '');
        $rawDriver = $this->input('driver_id', null);

        if ($rideId <= 0 || $date === '' || $time === '') {
            $this->json(['success' => false, 'error' => 'Missing fields.'], 422);
        }

        // Reschedule
        $this->services->reschedule($rideId, $date, strlen($time) === 5 ? $time . ':00' : $time);

        // Driver assignment
        if ($rawDriver === 'unassign') {
            $this->services->unassignDriver($rideId);
        } elseif ($rawDriver !== null && ctype_digit((string) $rawDriver) && (int) $rawDriver > 0) {
            $driverId  = (int) $rawDriver;
            $companyId = Session::companyId();
            if ($companyId !== null && !$this->users->isInCompany($driverId, $companyId)) {
                $this->json(['success' => false, 'error' => 'Driver not found or not in your company.'], 403);
            }
            $this->services->assignDriver($rideId, $driverId);
        }

        $this->json(['success' => true]);
    }
}
