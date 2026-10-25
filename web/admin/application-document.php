<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$admin = require_role($pdo, ['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

$applicationId = (int) ($_GET['id'] ?? 0);
$document = (string) ($_GET['document'] ?? '');
$column = match ($document) {
    'photo_2x2' => 'photo_2x2_path',
    'enrollment_form' => 'enrollment_form_path',
    default => null,
};
if ($applicationId < 1 || $column === null) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare("SELECT {$column} FROM scholarship_applications WHERE id = ?");
$stmt->execute([$applicationId]);
$filename = $stmt->fetchColumn();
$path = is_string($filename) && $filename !== '' ? App\Services\ApplicationDocumentService::path($filename) : null;
if ($path === null) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
$allowedMimes = $document === 'photo_2x2'
    ? ['image/jpeg', 'image/png']
    : ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(404);
    exit;
}

$extension = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'application/pdf' => 'pdf',
};
$downloadName = ($document === 'photo_2x2' ? '2x2-photo.' : 'enrollment-form.') . $extension;
audit_log($pdo, (int) $admin['id'], 'application_document_viewed', 'scholarship_application', $applicationId, 'Viewed ' . $document);

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $downloadName . '"');
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
if ($mime !== 'application/pdf') {
    header("Content-Security-Policy: sandbox");
}
readfile($path);