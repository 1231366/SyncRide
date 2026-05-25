<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;

final class DriverStatsController extends BaseController
{
    private ServiceRepository $services;
    private UserRepository    $users;

    public function __construct()
    {
        $this->services = ServiceRepository::default();
        $this->users    = UserRepository::default();
    }

    /** GET /admin/driver-stats.php */
    public function index(): void
    {
        $driverId  = isset($_GET['driver_id'])  && $_GET['driver_id']  !== '' ? (int) $_GET['driver_id']  : null;
        $partnerId = isset($_GET['partner_id']) && $_GET['partner_id'] !== '' ? (int) $_GET['partner_id'] : null;
        $startDate = $this->validDate((string) ($_GET['start_date'] ?? ''), date('Y-01-01'));
        $endDate   = $this->validDate((string) ($_GET['end_date']   ?? ''), date('Y-12-31'));

        $drivers  = $this->users->byRole(2);
        $partners = $this->users->byRole(3);

        if ($driverId !== null) {
            $subject = $this->users->find($driverId);
            $data    = $this->driverData($driverId, $startDate, $endDate, $subject?->name ?? 'Driver');
        } elseif ($partnerId !== null) {
            $subject = $this->users->find($partnerId);
            $data    = $this->partnerData($partnerId, $startDate, $endDate, $subject?->name ?? 'Partner');
        } else {
            $data = $this->overviewData($startDate, $endDate);
        }

        $this->view('admin.driver-stats.index', array_merge($data, [
            'drivers'   => $drivers,
            'partners'  => $partners,
            'driverId'  => $driverId,
            'partnerId' => $partnerId,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]));
    }

    private function driverData(int $driverId, string $startDate, string $endDate, string $name): array
    {
        $stats      = $this->services->driverStats($driverId, $startDate, $endDate);
        $chartData  = $this->services->driverMonthly($driverId, $startDate, $endDate);
        $tableRows  = $this->services->driverRecentRides($driverId, $startDate, $endDate);
        $avgRating  = $stats['avg_rating'] !== null ? number_format((float) $stats['avg_rating'], 1) : '5.0';

        return [
            'mode'       => 'driver',
            'subjectName'=> $name,
            'box1'       => ['val' => (int) $stats['trips_today'],  'lbl' => 'Today',          'icon' => 'navigation',    'color' => 'text-blue-500'],
            'box2'       => ['val' => $avgRating,                   'lbl' => 'Rating',          'icon' => 'star',          'color' => 'text-orange-500'],
            'box3'       => ['val' => (int) $stats['trips_period'], 'lbl' => 'In Period',       'icon' => 'calendar-range','color' => 'text-purple-500'],
            'box4'       => ['val' => (int) $stats['trips_total'],  'lbl' => 'All Time',        'icon' => 'trophy',        'color' => 'text-emerald-500'],
            'chartData'  => $chartData,
            'tableTitle' => 'Recent Rides',
            'tableRows'  => $tableRows,
            'leaderboard'=> [],
        ];
    }

    private function partnerData(int $partnerId, string $startDate, string $endDate, string $name): array
    {
        $stats     = $this->services->partnerStats($partnerId, $startDate, $endDate);
        $chartData = $this->services->partnerMonthly($partnerId, $startDate, $endDate);
        $tableRows = $this->services->partnerRecentRides($partnerId, $startDate, $endDate);

        return [
            'mode'       => 'partner',
            'subjectName'=> $name,
            'box1'       => ['val' => (int) $stats['trips_today'],  'lbl' => 'Today',     'icon' => 'megaphone',     'color' => 'text-blue-500'],
            'box2'       => ['val' => 'Active',                     'lbl' => 'Status',    'icon' => 'building',      'color' => 'text-blue-500'],
            'box3'       => ['val' => (int) $stats['trips_period'], 'lbl' => 'In Period', 'icon' => 'calendar-range','color' => 'text-purple-500'],
            'box4'       => ['val' => (int) $stats['trips_total'],  'lbl' => 'All Time',  'icon' => 'check-circle',  'color' => 'text-emerald-500'],
            'chartData'  => $chartData,
            'tableTitle' => 'Recent Services',
            'tableRows'  => $tableRows,
            'leaderboard'=> [],
        ];
    }

    private function overviewData(string $startDate, string $endDate): array
    {
        $stats       = $this->services->overviewStats($startDate, $endDate);
        $chartData   = $this->services->monthlyByPeriod($startDate, $endDate);
        $driverBoard = $this->services->driverLeaderboard($startDate, $endDate);
        $partnerBoard= $this->services->partnerLeaderboard($startDate, $endDate);

        return [
            'mode'       => 'overview',
            'subjectName'=> 'Overview',
            'box1'       => ['val' => $stats['driverCount'],  'lbl' => 'Drivers',    'icon' => 'users',      'color' => 'text-blue-500'],
            'box2'       => ['val' => $stats['todayCount'],   'lbl' => 'Today',      'icon' => 'car',        'color' => 'text-emerald-500'],
            'box3'       => ['val' => $stats['periodCount'],  'lbl' => 'In Period',  'icon' => 'bar-chart-2','color' => 'text-purple-500'],
            'box4'       => ['val' => $stats['totalCount'],   'lbl' => 'All Time',   'icon' => 'globe',      'color' => 'text-emerald-500'],
            'chartData'  => $chartData,
            'tableTitle' => 'Driver Rankings',
            'tableRows'  => [],
            'leaderboard'=> ['drivers' => $driverBoard, 'partners' => $partnerBoard],
        ];
    }

    private function validDate(string $value, string $fallback): string
    {
        return ($value !== '' && strtotime($value) !== false) ? $value : $fallback;
    }
}
