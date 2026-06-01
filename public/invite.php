<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
use App\Http\Controllers\InviteController;
(new InviteController())->show();
