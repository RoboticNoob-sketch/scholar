<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$viewer = current_user($pdo) ?? authenticate_api_token($pdo, bearer_token());
if (!$viewer || !in_array($viewer['role'], ['admin', 'staff', 'student'], true)) {
    http_response_code(404);
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare('SELECT photo_path, user_id FROM scholars WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || empty($row['photo_path'])) {
    http_response_code(404);
    exit;
}
if ($viewer['role'] === 'student' && (int) $viewer['id'] !== (int) $row['user_id']) {
    http_response_code(404);
    exit;
}

$path = scholar_uploads_dir() . '/' . basename((string) $row['photo_path']);
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . $mime);
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
