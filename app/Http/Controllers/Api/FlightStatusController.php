<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\FlightStatusService;

/**
 * GET /api/flight-status.php?flight=TP1934&date=2026-07-01
 *
 * Read-only, heavily cached server-side (see FlightStatusService).
 * Answers ['found' => false] for anything it can't resolve — the UI
 * treats that as "show nothing".
 */
final class FlightStatusController extends BaseController
{
    public function show(): never
    {
        $flight = (string) $this->input('flight', '');
        $date   = (string) $this->input('date', date('Y-m-d'));

        $this->json(FlightStatusService::default()->get($flight, $date));
    }
}
