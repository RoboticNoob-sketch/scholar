<?php

declare(strict_types=1);

function current_user(PDO $pdo): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND status = "active"');
    $stmt->execute([$_SESSION['user_id']]);
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

function require_login(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        redirect('login.php');
    }
    return $user;
}

function require_role(PDO $pdo, array $roles): array
{
    $user = require_login($pdo);
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function create_api_token(PDO $pdo, int $userId): string
{
    $token = generate_token(32);
    $hash = hash('sha256', $token);
    $ttl = app_config()['api_token_ttl_hours'];
    $expires = (new DateTimeImmutable("+{$ttl} hours"))->format('Y-m-d H:i:s');

    $pdo->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$userId]);
    $stmt = $pdo->prepare('INSERT INTO api_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $hash, $expires]);

    return $token;
}

function authenticate_api_token(PDO $pdo, ?string $token): ?array
{
    if (!$token) {
        return null;
    }

    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare(
        'SELECT u.* FROM api_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.status = "active"'
    );
    $stmt->execute([$hash]);
    return $stmt->fetch() ?: null;
}

function bearer_token(): ?string
{
    resolve_authorization_header();

    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
        return $matches[1];
    }
    return $_GET['token'] ?? null;
}

function resolve_authorization_header(): void
{
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return;
    }
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        return;
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp((string) $name, 'Authorization') === 0) {
                    $_SERVER['HTTP_AUTHORIZATION'] = $value;
                    return;
                }
            }
        }
    }
}

/** Staff/student API routes: Bearer token (mobile) or PHP session (web scanner). */
function require_api_role(PDO $pdo, array $roles): array
{
    resolve_authorization_header();
    $token = bearer_token();
    if ($token) {
        $user = authenticate_api_token($pdo, $token);
        if ($user && in_array($user['role'], $roles, true)) {
            return $user;
        }
        json_response(['success' => false, 'error' => 'Unauthorized'], 401);
    }

    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'error' => 'Unauthorized'], 401);
    }
    $user = current_user($pdo);
    if (!$user || !in_array($user['role'], $roles, true)) {
        json_response(['success' => false, 'error' => 'Forbidden'], 403);
    }
    return $user;
}
