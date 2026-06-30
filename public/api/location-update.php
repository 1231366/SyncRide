<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\Controllers\Api\LocationController;
// No hard auth gate: background GPS posts from the native app may arrive after
// the PHP session has been garbage-collected (app killed / doze). We start the
// session softly (so a live cookie is still honoured) and let the controller
// authorise the post by matching driver_id to the ride's assigned driver.
App\Support\Session::start();
(new LocationController())->update();
