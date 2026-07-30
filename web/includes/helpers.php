<?php

declare(strict_types=1);

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/load.php';
    }
    return $config;
}

function base_url(string $path = ''): string
{
    $base = rtrim(app_config()['base_url'], '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }
    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $value;
}

function format_money(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    return date('M j, Y', strtotime($date));
}

function format_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    return date('M j, Y g:i A', strtotime($datetime));
}

function scholar_full_name(array $row): string
{
    return trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
}

function profile_qr_payload(array $scholar): string
{
    return 'SCH|' . $scholar['public_id'] . '|' . $scholar['qr_token'];
}

function voucher_qr_payload(string $voucherCode): string
{
    return 'VCH|' . $voucherCode;
}

function generate_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function generate_voucher_code(): string
{
    return 'VCH-' . strtoupper(substr(generate_token(8), 0, 4)) . '-' . strtoupper(substr(generate_token(8), 0, 4));
}

function generate_public_id(PDO $pdo): string
{
    do {
        $id = 'SCH' . str_pad((string) random_int(1, 999999999999), 12, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare('SELECT id FROM scholars WHERE public_id = ?');
        $stmt->execute([$id]);
    } while ($stmt->fetch());

    return $id;
}

function audit_log(PDO $pdo, ?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $action,
        $entityType,
        $entityId,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function badge_class(string $status): string
{
    return match ($status) {
        'claimed', 'active', 'open', 'accent' => 'badge-accent',
        'pending', 'draft', 'warning' => 'badge-warning',
        'expired', 'inactive', 'closed' => 'badge-muted',
        'void', 'removed', 'negative' => 'badge-negative',
        'admin' => 'badge-accent',
        'staff' => 'badge-warning',
        'student' => 'badge-muted',
        default => 'badge-muted',
    };
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Admin',
        'staff' => 'Staff',
        'student' => 'Student',
        default => ucfirst($role),
    };
}

function profile_verify_ttl_minutes(): int
{
    return max(1, (int) (app_config()['profile_verify_ttl_minutes'] ?? 5));
}

function issue_profile_verification(PDO $pdo, int $staffId, int $scholarId, ?int $batchId = null): string
{
    $pdo->prepare(
        'UPDATE staff_profile_verifications SET consumed_at = NOW()
         WHERE staff_user_id = ? AND consumed_at IS NULL AND expires_at > NOW()'
    )->execute([$staffId]);

    $token = generate_token(24);
    $hash = hash('sha256', $token);
    $ttl = profile_verify_ttl_minutes();
    $pdo->prepare(
        'INSERT INTO staff_profile_verifications (staff_user_id, scholar_id, batch_id, token_hash, expires_at)
         VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    )->execute([$staffId, $scholarId, $batchId, $hash, $ttl]);

    return $token;
}

function validate_profile_verification(PDO $pdo, int $staffId, string $token, int $scholarId): ?array
{
    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare(
        'SELECT * FROM staff_profile_verifications
         WHERE token_hash = ? AND staff_user_id = ? AND scholar_id = ?
           AND consumed_at IS NULL AND expires_at > NOW()'
    );
    $stmt->execute([$hash, $staffId, $scholarId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function consume_profile_verification(PDO $pdo, int $verificationId): void
{
    $pdo->prepare('UPDATE staff_profile_verifications SET consumed_at = NOW() WHERE id = ?')
        ->execute([$verificationId]);
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}
