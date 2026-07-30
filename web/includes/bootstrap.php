<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/load.php';

date_default_timezone_set($config['timezone'] ?? 'Asia/Manila');

if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = $config['allowed_origins'] ?? null;
    if (is_string($allowed)) {
        $allowed = array_map('trim', explode(',', $allowed));
    }
    if (is_array($allowed) && $allowed !== []) {
        if ($origin !== '' && in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

session_name($config['session_name']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$pdo = db_connect($config);
