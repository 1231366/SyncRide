<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'     => 'mysql',
            'host'       => Env::get('DB_HOST', '127.0.0.1'),
            'port'       => (int) Env::get('DB_PORT', 3306),
            'database'   => Env::require('DB_DATABASE'),
            'username'   => Env::require('DB_USERNAME'),
            'password'   => Env::get('DB_PASSWORD', ''),
            'charset'    => Env::get('DB_CHARSET', 'utf8mb4'),
            'persistent' => (bool) Env::get('DB_PERSISTENT', false),
        ],
    ],
];
