<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'name'     => Env::get('APP_NAME', 'SyncRide'),
    'env'      => Env::get('APP_ENV', 'production'),
    'debug'    => (bool) Env::get('APP_DEBUG', false),
    'url'      => Env::get('APP_URL', 'http://localhost'),
    'timezone' => Env::get('APP_TIMEZONE', 'UTC'),
    'key'      => Env::get('APP_KEY'),
];
