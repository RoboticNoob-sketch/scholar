<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

$localPath = __DIR__ . '/config.local.php';
if (is_file($localPath)) {
    $local = require $localPath;
    $config = array_replace_recursive($config, $local);
}

return $config;
