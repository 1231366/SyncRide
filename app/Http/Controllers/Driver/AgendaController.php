<?php

declare(strict_types=1);

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;

final class AgendaController extends BaseController
{
    private ServiceRepository $services;

    public function __construct()
    {
        $this->services = ServiceRepository::forDriverContext();
    }

    /** GET /driver/agenda.php */
    public function index(): void
    {
        $driverId     = (int) ($_SESSION['user_id'] ?? 0);
        $selectedDate = $this->validDate((string) ($_GET['date'] ?? ''), date('Y-m-d'));
        $rides        = $this->services->forDriver($driverId, $selectedDate);
        $yearMonth    = (new \DateTimeImmutable($selectedDate))->format('Y-m');
        $ridesPerDay  = $this->services->forDriverMonthCounts($driverId, $yearMonth);

        $this->view('driver.agenda.index', compact('selectedDate', 'rides', 'ridesPerDay'));
    }

    private function validDate(string $value, string $fallback): string
    {
        return ($value !== '' && strtotime($value) !== false) ? $value : $fallback;
    }
}
