<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = authenticate_api_token($pdo, bearer_token());
if (!$user || $user['role'] !== 'student') {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT photo_2x2_path FROM scholarship_applications
     WHERE applicant_user_id = ? AND photo_2x2_path IS NOT NULL
     ORDER BY updated_at DESC, id DESC LIMIT 1'
);
$stmt->execute([(int) $user['id']]);
$filename = $stmt->fetchColumn();
$path = is_string($filename) ? App\Services\ApplicationDocumentService::path($filename) : null;
if ($path === null) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
