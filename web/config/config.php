<?php

declare(strict_types=1);

return [
    'app_name' => 'Scholarly',
    'school_name' => 'Southern Luzon State University — Tiaong Campus',
    'school_short' => 'SLSU Tiaong · Scholarship Office',
    'base_url' => '/scholarship-qr-monitor/web',
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'scholarly_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'session_name' => 'scholarly_session',
    'api_token_ttl_hours' => 168,
    'profile_verify_ttl_minutes' => 5,
    'timezone' => 'Asia/Manila',
];
