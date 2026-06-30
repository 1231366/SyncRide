<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\Services\PricingEngine;
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

        // Cor determinística a partir do driver_id — TEM de ser idêntica à fórmula
        // da legenda no JS (PALETTE[id % len]), senão o mesmo motorista aparece com
        // cores diferentes entre a grelha e a legenda.
        $palette = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#14b8a6'];
        $events  = [];

        foreach ($rides as $r) {
            $driverId = $r['driver_id'] ? (int) $r['driver_id'] : null;
            $color = $driverId !== null ? $palette[$driverId % count($palette)] : '#64748b';

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
                    'driver_id'    => $driverId,
                    'driver_name'  => $r['driver_name'] ?? null,
                    'driver_color' => $color,
                    'client'       => $r['NomeCliente']      ?? '',
                    'pickup'       => $r['serviceStartPoint']  ?? '',
                    'dropoff'      => $r['serviceTargetPoint'] ?? '',
                    'flight'       => $r['FlightNumber']       ?? '',
                    'pax_adt'      => (int) $r['paxADT'],
                    'pax_chd'      => (int) $r['paxCHD'],
                    'pax_bby'      => (int) $r['paxBBY'],
                    'type'         => (int) $r['serviceType'],
                    // Cockpit: estado operacional ao vivo + timestamps de cada etapa.
                    'status_id'    => (int) ($r['status_id'] ?? 0),
                    'ts'           => [
                        'start_pickup'   => $r['ts_start_pickup']   ?? null,
                        'arrived_pickup' => $r['ts_arrived_pickup'] ?? null,
                        'with_client'    => $r['ts_with_client']    ?? null,
                        'start_trip'     => $r['ts_start_trip']     ?? null,
                        'completed'      => $r['ts_completed']      ?? null,
                    ],
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

        // Optional: edição rápida do nº de voo a partir do Quadro (cockpit).
        if (array_key_exists('flight', $_POST)) {
            $this->services->setFlightNumber($rideId, trim((string) $this->input('flight', '')));
        }

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

            // Custo do motorista fecha na atribuição — base = default do motorista.
            $ride = $this->services->find($rideId);
            if ($ride !== null) {
                $basis  = $this->users->defaultPayBasis($driverId);
                $payout = PricingEngine::default()->driverPayout(
                    $ride->resort, $ride->vehicleLabel, $ride->type,
                    $ride->totalPax(), $ride->hotelExtra, $basis
                );
                $this->services->setDriverPricing($rideId, $basis, $payout);
            }
        }

        $this->json(['success' => true]);
    }
}
