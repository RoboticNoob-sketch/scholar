<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$configPath = __DIR__ . '/config/config.local.php';
$hasLocal = is_file($configPath);

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Scholarly health check</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem}';
echo 'code{background:#f3f4f6;padding:.15rem .35rem;border-radius:4px}.ok{color:#059669}.bad{color:#dc2626}';
echo 'li{margin:.5rem 0}</style></head><body>';
echo '<h1>Scholarly health check</h1><ul>';

echo '<li>PHP version: <strong>' . e(PHP_VERSION) . '</strong></li>';
echo '<li>config.local.php: ';
echo $hasLocal ? '<span class="ok">found</span>' : '<span class="bad">missing — create web/config/config.local.php</span>';
echo '</li>';

if ($hasLocal) {
    $config = require __DIR__ . '/config/load.php';
    echo '<li>base_url: <code>' . e($config['base_url'] ?? '') . '</code> (use empty string for domain root)</li>';
    echo '<li>database: <code>' . e($config['db']['name'] ?? '') . '</code></li>';

    try {
        require_once __DIR__ . '/includes/core/db.php';
        $pdo = db_connect($config);
        echo '<li>MySQL connection: <span class="ok">OK</span></li>';

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo '<li>Tables: <strong>' . count($tables) . '</strong>';
        if ($tables === []) {
            echo ' <span class="bad">— import database/hostinger-schema.sql in phpMyAdmin</span>';
        } else {
            echo ' <span class="ok">— schema looks loaded</span>';
        }
        echo '</li>';
    } catch (Throwable $e) {
        echo '<li>MySQL connection: <span class="bad">FAILED</span> — check host, database name, user, and password in config.local.php</li>';
    }
} else {
    echo '<li>MySQL connection: <em>skipped until config.local.php exists</em></li>';
}

echo '</ul>';
echo '<p>Delete this file after setup: <code>web/health.php</code></p>';
echo '<p><a href="login.php">Try login page</a></p>';
echo '</body></html>';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
