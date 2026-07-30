<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_api_role($pdo, ['staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$payload = normalize_qr_payload(trim($body['payload'] ?? ''));
$batchId = (int) ($body['batch_id'] ?? 0) ?: null;

if ($payload === '') {
    json_response(['success' => false, 'error' => 'QR payload is required'], 422);
}

if (stripos($payload, 'VCH|') === 0 || preg_match('/^VCH[-A-Z0-9]+$/i', $payload)) {
    json_response([
        'success' => false,
        'error' => 'This is a voucher QR. Switch to Voucher Scan after profile verification.',
        'code' => 'wrong_qr_type',
    ], 400);
}

if (stripos($payload, 'SCH|') !== 0) {
    json_response(['success' => false, 'error' => 'Invalid profile QR code'], 400);
}

$parts = explode('|', $payload);
if (count($parts) !== 3) {
    json_response(['success' => false, 'error' => 'Malformed profile QR'], 400);
}

[, $publicId, $qrToken] = $parts;
$publicId = trim($publicId);
$qrToken = trim($qrToken);

$stmt = $pdo->prepare('SELECT * FROM scholars WHERE public_id = ? AND qr_token = ? AND status = "active"');
$stmt->execute([$publicId, $qrToken]);
$scholar = $stmt->fetch();

if (!$scholar) {
    audit_log($pdo, (int) $user['id'], 'profile_verify_failed', 'scholar', null, $publicId);
    json_response(['success' => false, 'error' => 'Profile not recognized or scholar is inactive'], 404);
}

$verificationToken = issue_profile_verification($pdo, (int) $user['id'], (int) $scholar['id'], $batchId);

audit_log($pdo, (int) $user['id'], 'profile_verified', 'scholar', (int) $scholar['id'], scholar_full_name($scholar));

json_response([
    'success' => true,
    'message' => 'Profile verified — scan voucher within ' . profile_verify_ttl_minutes() . ' minutes',
    'verification_token' => $verificationToken,
    'expires_in_minutes' => profile_verify_ttl_minutes(),
    'scholar' => [
        'id' => (int) $scholar['id'],
        'name' => scholar_full_name($scholar),
        'student_no' => $scholar['student_no'],
        'public_id' => $scholar['public_id'],
        'photo_url' => scholar_photo_url($scholar['photo_path'] ?? null, (int) $scholar['id']),
    ],
]);
