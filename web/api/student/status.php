<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = authenticate_api_token($pdo, bearer_token());
if (!$user || $user['role'] !== 'student') {
    json_response(['success' => false, 'error' => 'Unauthorized'], 401);
}

$stmt = $pdo->prepare('SELECT * FROM scholars WHERE user_id = ?');
$stmt->execute([$user['id']]);
$scholar = $stmt->fetch();
if (!$scholar) {
    json_response(['success' => false, 'error' => 'Scholar profile not found'], 404);
}

if ($scholar['status'] !== 'active') {
    json_response(['success' => false, 'error' => 'Your scholar account is inactive. Contact the scholarship office.'], 403);
}

$openVouchers = student_open_vouchers($pdo, (int) $scholar['id']);
$pendingVouchers = array_values(array_filter(
    $openVouchers,
    static fn (array $voucher): bool => $voucher['voucher_status'] === 'pending'
));
$primary = $pendingVouchers[0] ?? $openVouchers[0] ?? null;

json_response([
    'success' => true,
    'has_open_batch' => $openVouchers !== [],
    'pending_count' => count($pendingVouchers),
    'open_vouchers' => $openVouchers,
    'profile_qr' => profile_qr_payload($scholar),
    'scholar' => [
        'full_name' => scholar_full_name($scholar),
        'student_no' => $scholar['student_no'],
    ],
    'current_batch' => $primary,
]);
