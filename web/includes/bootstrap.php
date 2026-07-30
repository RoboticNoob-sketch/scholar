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

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/pagination.php';
require_once __DIR__ . '/datatables.php';
require_once __DIR__ . '/report_templates.php';
require_once __DIR__ . '/export.php';
require_once __DIR__ . '/pdf_report.php';

try {
    $pdo = db_connect($config);
} catch (PDOException $e) {
    $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
    if ($isApi) {
        header('Content-Type: application/json');
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    $hasLocal = is_file(__DIR__ . '/../config/config.local.php');
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database setup required</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:640px;margin:2rem auto;padding:0 1rem;line-height:1.5}';
    echo 'code{background:#f3f4f6;padding:.15rem .35rem;border-radius:4px}</style></head><body>';
    echo '<h1>Database connection failed</h1>';
    if (!$hasLocal) {
        echo '<p>Create <code>web/config/config.local.php</code> on the server with your Hostinger MySQL credentials.</p>';
    } else {
        echo '<p>Check the database name, user, and password in <code>web/config/config.local.php</code>.</p>';
        echo '<p>Also confirm you imported <code>database/hostinger-schema.sql</code> in phpMyAdmin.</p>';
    }
    echo '<p><a href="health.php">Run health check</a></p></body></html>';
    exit;
}
