<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\Controllers\Api\LocationController;
// See location-update.php — soft session, controller authorises by ride assignment.
App\Support\Session::start();
(new LocationController())->upload();
