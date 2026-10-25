<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class GoogleOAuthService
{
    public static function configured(): bool
    {
        $settings = self::settings();
        return $settings['client_id'] !== '' && $settings['client_secret'] !== '';
    }

    public static function begin(): void
    {
        if (!self::configured()) {
            throw new RuntimeException('Google sign-in has not been configured.');
        }

        $settings = self::settings();
        $state = self::randomUrlSafe(32);
        $nonce = self::randomUrlSafe(32);
        $verifier = self::randomUrlSafe(48);
        $_SESSION['google_oauth'] = [
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'created_at' => time(),
        ];

        $query = http_build_query([
            'client_id' => $settings['client_id'],
            'redirect_uri' => self::redirectUri($settings),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => self::base64Url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ]);

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $query, true, 302);
        exit;
    }

    public static function complete(PDO $pdo): void
    {
        $session = $_SESSION['google_oauth'] ?? null;
        unset($_SESSION['google_oauth']);

        $state = (string) ($_GET['state'] ?? '');
        if (!is_array($session)
            || !hash_equals((string) ($session['state'] ?? ''), $state)
            || time() - (int) ($session['created_at'] ?? 0) > 600
            || isset($_GET['error'])) {
            throw new RuntimeException('Google sign-in expired or was cancelled. Please try again.');
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            throw new RuntimeException('Google did not return an authorization code.');
        }

        $settings = self::settings();
        $tokenResponse = self::exchangeCode($settings, $code, (string) $session['verifier']);
        $claims = self::verifyIdToken((string) ($tokenResponse['id_token'] ?? ''), $settings['client_id'], (string) $session['nonce']);
        $user = self::findOrCreateStudent($pdo, $claims);

        session_regenerate_id(true);
        login_user($user);
        audit_log($pdo, (int) $user['id'], 'google_login', 'user', (int) $user['id'], 'Student signed in with Google');
        redirect('student/applications.php');
    }

    private static function findOrCreateStudent(PDO $pdo, array $claims): array
    {
        $email = strtolower(trim((string) $claims['email']));
        $subject = (string) $claims['sub'];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE google_sub = ? FOR UPDATE');
            $stmt->execute([$subject]);
            $user = $stmt->fetch() ?: null;

            if (!$user) {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? FOR UPDATE');
                $stmt->execute([$email]);
                $user = $stmt->fetch() ?: null;
            }

            if (!$user) {
                $stmt = $pdo->prepare(
                    'SELECT u.* FROM users u JOIN scholars s ON s.user_id = u.id
                     WHERE u.role = "student" AND LOWER(s.email) = ? LIMIT 2 FOR UPDATE'
                );
                $stmt->execute([$email]);
                $matches = $stmt->fetchAll();
                if (count($matches) === 1) {
                    $user = $matches[0];
                } elseif (count($matches) > 1) {
                    throw new RuntimeException('More than one student profile uses this email. Contact the SK office.');
                }
            }

            if ($user) {
                if ($user['role'] !== 'student' || $user['status'] !== 'active') {
                    throw new RuntimeException('This Google account cannot access the student portal.');
                }
                if (!empty($user['google_sub']) && !hash_equals((string) $user['google_sub'], $subject)) {
                    throw new RuntimeException('This email is linked to another Google account. Contact the SK office.');
                }
                $pdo->prepare('UPDATE users SET email = ?, google_sub = ? WHERE id = ?')
                    ->execute([$email, $subject, (int) $user['id']]);
                $user['email'] = $email;
                $user['google_sub'] = $subject;
            } else {
                $username = 'google_' . substr(hash('sha256', $subject), 0, 32);
                $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                $pdo->prepare(
                    'INSERT INTO users (username, email, google_sub, password_hash, role, status)
                     VALUES (?, ?, ?, ?, "student", "active")'
                )->execute([$username, $email, $subject, $passwordHash]);
                $user = [
                    'id' => (int) $pdo->lastInsertId(),
                    'username' => $username,
                    'email' => $email,
                    'google_sub' => $subject,
                    'role' => 'student',
                    'status' => 'active',
                ];
            }

            $pdo->commit();
            return $user;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    private static function exchangeCode(array $settings, string $code, string $verifier): array
    {
        $response = self::request('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $settings['client_id'],
            'client_secret' => $settings['client_secret'],
            'redirect_uri' => self::redirectUri($settings),
            'grant_type' => 'authorization_code',
            'code_verifier' => $verifier,
        ]);
        if (!is_array($response) || empty($response['id_token'])) {
            throw new RuntimeException('Google did not return a valid sign-in token.');
        }
        return $response;
    }

    private static function verifyIdToken(string $token, string $clientId, string $expectedNonce): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Google returned an invalid identity token.');
        }

        [$encodedHeader, $encodedClaims, $encodedSignature] = $parts;
        $header = json_decode(self::base64UrlDecode($encodedHeader), true);
        $claims = json_decode(self::base64UrlDecode($encodedClaims), true);
        $signature = self::base64UrlDecode($encodedSignature);
        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
            throw new RuntimeException('Google returned an unsupported identity token.');
        }

        $certificates = self::googleCertificates();
        $certificate = $certificates[$header['kid']] ?? null;
        if (!is_string($certificate)
            || openssl_verify($encodedHeader . '.' . $encodedClaims, $signature, $certificate, OPENSSL_ALGO_SHA256) !== 1) {
            throw new RuntimeException('Google identity token signature could not be verified.');
        }

        $audiences = (array) ($claims['aud'] ?? []);
        $issuer = (string) ($claims['iss'] ?? '');
        $emailVerified = in_array($claims['email_verified'] ?? false, [true, 1, '1', 'true'], true);
        $now = time();
        if (!in_array($clientId, $audiences, true)
            || !in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
            || (int) ($claims['exp'] ?? 0) <= $now
            || (int) ($claims['iat'] ?? 0) > $now + 60
            || !hash_equals($expectedNonce, (string) ($claims['nonce'] ?? ''))
            || !$emailVerified
            || empty($claims['email'])
            || empty($claims['sub'])) {
            throw new RuntimeException('Google account verification failed. Please retry or contact the SK office.');
        }

        return $claims;
    }

    private static function googleCertificates(): array
    {
        $certificates = self::request('https://www.googleapis.com/oauth2/v1/certs');
        if (!is_array($certificates) || $certificates === []) {
            throw new RuntimeException('Google certificate verification is temporarily unavailable.');
        }
        return $certificates;
    }

    private static function request(string $url, ?array $form = null): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Google sign-in could not be initialized.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        if ($form !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($form));
        }

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        $data = is_string($body) ? json_decode($body, true) : null;
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            throw new RuntimeException('Google sign-in request failed. Please try again later.');
        }
        return $data;
    }

    private static function settings(): array
    {
        $settings = app_config()['google_oauth'] ?? [];
        return [
            'client_id' => trim((string) ($settings['client_id'] ?? '')),
            'client_secret' => trim((string) ($settings['client_secret'] ?? '')),
            'redirect_uri' => trim((string) ($settings['redirect_uri'] ?? '')),
        ];
    }

    private static function redirectUri(array $settings): string
    {
        return $settings['redirect_uri'] !== ''
            ? $settings['redirect_uri']
            : absolute_url('auth/google-callback.php');
    }

    private static function randomUrlSafe(int $bytes): string
    {
        return self::base64Url(random_bytes($bytes));
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if (!is_string($decoded)) {
            throw new RuntimeException('Google returned an invalid identity token.');
        }
        return $decoded;
    }
}