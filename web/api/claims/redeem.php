<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_api_role($pdo, ['staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$batchId = (int) ($body['batch_id'] ?? 0);
$payload = normalize_qr_payload(trim($body['payload'] ?? ''));
$verificationToken = trim($body['verification_token'] ?? '');

if (!$batchId || $payload === '') {
    json_response(['success' => false, 'error' => 'Batch and QR payload are required'], 422);
}

if ($verificationToken === '') {
    audit_log($pdo, (int) $user['id'], 'scan_failed', 'claim_voucher', null, 'Redeem without profile verification');
    json_response([
        'success' => false,
        'error' => 'Profile verification required. Scan the scholar profile QR first.',
        'code' => 'profile_required',
    ], 403);
}

if (stripos($payload, 'SCH|') === 0) {
    audit_log($pdo, (int) $user['id'], 'scan_failed', 'claim_voucher', null, 'Profile QR scanned as voucher');
    json_response([
        'success' => false,
        'error' => 'This is a profile QR. Use Profile Verify first, then scan the voucher QR.',
        'code' => 'wrong_qr_type',
    ], 400);
}

if (stripos($payload, 'VCH|') !== 0) {
    if (preg_match('/^VCH[-A-Z0-9]+$/i', $payload)) {
        $payload = 'VCH|' . $payload;
    } else {
        audit_log($pdo, (int) $user['id'], 'scan_failed', 'claim_voucher', null, 'Invalid QR format');
        json_response(['success' => false, 'error' => 'Invalid voucher QR code'], 400);
    }
}

$voucherCode = substr($payload, 4);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT v.*, b.status AS batch_status, b.name AS batch_name,
                s.id AS scholar_id, s.student_no, s.first_name, s.last_name, s.public_id, s.status AS scholar_status
         FROM claim_vouchers v
         JOIN distribution_batches b ON b.id = v.batch_id
         JOIN scholars s ON s.id = v.scholar_id
         WHERE v.voucher_code = ? AND v.batch_id = ?
         FOR UPDATE'
    );
    $stmt->execute([$voucherCode, $batchId]);
    $voucher = $stmt->fetch();

    if (!$voucher) {
        $pdo->rollBack();
        audit_log($pdo, (int) $user['id'], 'scan_failed', 'claim_voucher', null, "Voucher not found: $voucherCode");
        json_response(['success' => false, 'error' => 'Voucher not found for this batch'], 404);
    }

    if ($voucher['scholar_status'] !== 'active') {
        $pdo->rollBack();
        audit_log($pdo, (int) $user['id'], 'scan_failed', 'claim_voucher', (int) $voucher['id'], 'Inactive scholar');
        json_response(['success' => false, 'error' => 'This scholar account is inactive'], 403);
    }

    $verification = validate_profile_verification(
        $pdo,
        (int) $user['id'],
        $verificationToken,
        (int) $voucher['scholar_id']
    );
    if (!$verification) {
        $pdo->rollBack();
        audit_log($pdo, (int) $user['id'], 'scan_failed', 'claim_voucher', (int) $voucher['id'], 'Invalid or expired profile verification');
        json_response([
            'success' => false,
            'error' => 'Profile verification expired or does not match this scholar. Scan profile QR again.',
            'code' => 'verification_invalid',
        ], 403);
    }

    if ($voucher['batch_status'] !== 'open') {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'This batch is not open for claiming'], 409);
    }

    if ($voucher['status'] === 'claimed') {
        $pdo->rollBack();
        audit_log($pdo, (int) $user['id'], 'scan_failed', 'claim_voucher', (int) $voucher['id'], 'Already claimed');
        json_response(['success' => false, 'error' => 'This voucher was already claimed'], 409);
    }

    if (in_array($voucher['status'], ['expired', 'void'], true)) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'This voucher is ' . $voucher['status']], 409);
    }

    if ($voucher['expires_at'] && strtotime($voucher['expires_at']) < time()) {
        $pdo->prepare('UPDATE claim_vouchers SET status="expired" WHERE id=?')->execute([$voucher['id']]);
        $pdo->commit();
        json_response(['success' => false, 'error' => 'This voucher has expired'], 409);
    }

    $pdo->prepare('UPDATE claim_vouchers SET status="claimed" WHERE id=?')->execute([$voucher['id']]);
    $pdo->prepare('INSERT INTO claims (voucher_id, staff_user_id, profile_verified) VALUES (?, ?, 1)')
        ->execute([$voucher['id'], $user['id']]);

    consume_profile_verification($pdo, (int) $verification['id']);

    audit_log($pdo, (int) $user['id'], 'claim_redeemed', 'claim_voucher', (int) $voucher['id'], scholar_full_name($voucher) . ' (profile verified)');
    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Claim recorded successfully',
        'profile_verified' => true,
        'amount' => format_money((float) $voucher['amount']),
        'scholar' => [
            'name' => scholar_full_name($voucher),
            'student_no' => $voucher['student_no'],
            'public_id' => $voucher['public_id'],
        ],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'error' => 'Server error while redeeming voucher'], 500);
}
