<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare('SELECT photo_path FROM scholars WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || empty($row['photo_path'])) {
    http_response_code(404);
    exit;
}

$path = scholar_uploads_dir() . '/' . basename((string) $row['photo_path']);
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
