<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\Controllers\Api\AiSyncController;
(new AiSyncController())->index();
