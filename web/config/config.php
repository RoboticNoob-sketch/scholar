<?php

declare(strict_types=1);

return [
    'app_name' => 'Scholarly',
    'school_name' => 'Southern Luzon State University — Tiaong Campus',
    'school_short' => 'SLSU Tiaong · Scholarship Office',
    'mobile_download_url' => 'https://github.com/RoboticNoob-sketch/scholar/releases/latest/download/scholarly.apk',
    'base_url' => '/scholarship-qr-monitor/web',
    'private_application_storage_dir' => '',
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
    'google_oauth' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => '',
    ],
    'timezone' => 'Asia/Manila',
];
