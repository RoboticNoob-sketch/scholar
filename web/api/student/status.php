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

$openVoucher = $pdo->prepare(
    'SELECT v.*, b.name AS batch_name, b.distribution_date, b.venue, b.status AS batch_status,
            p.name AS program_name
     FROM claim_vouchers v
     JOIN distribution_batches b ON b.id = v.batch_id
     JOIN scholarship_programs p ON p.id = b.program_id
     WHERE v.scholar_id = ? AND b.status = "open"
     ORDER BY (v.status = "pending") DESC, b.distribution_date DESC, b.id DESC
     LIMIT 1'
);
$openVoucher->execute([$scholar['id']]);
$voucher = $openVoucher->fetch();

$response = [
    'success' => true,
    'has_open_batch' => (bool) $voucher,
    'profile_qr' => profile_qr_payload($scholar),
    'scholar' => [
        'full_name' => scholar_full_name($scholar),
        'student_no' => $scholar['student_no'],
    ],
];

if ($voucher) {
    $response['current_batch'] = [
        'batch_name' => $voucher['batch_name'],
        'program_name' => $voucher['program_name'],
        'venue' => $voucher['venue'],
        'distribution_date' => $voucher['distribution_date'],
        'amount' => (float) $voucher['amount'],
        'amount_formatted' => format_money((float) $voucher['amount']),
        'voucher_status' => $voucher['status'],
        'voucher_qr' => $voucher['status'] === 'pending' ? voucher_qr_payload($voucher['voucher_code']) : null,
        'claimed_at' => null,
    ];
    if ($voucher['status'] === 'claimed') {
        $claim = $pdo->prepare('SELECT claimed_at FROM claims WHERE voucher_id = ?');
        $claim->execute([$voucher['id']]);
        $response['current_batch']['claimed_at'] = $claim->fetchColumn() ?: null;
    }
} else {
    $response['current_batch'] = null;
}

json_response($response);
