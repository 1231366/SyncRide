<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\Controllers\Api\TrackingController;
// No hard auth gate: a "stop" may arrive in the background after the session
// lapsed. TrackingController::stop() starts the session and falls back to the
// payload driver_id, requiring a non-zero driver before deleting the row.
(new TrackingController())->stop();
