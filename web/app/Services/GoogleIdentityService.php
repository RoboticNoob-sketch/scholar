<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class GoogleIdentityService
{
    public static function signIn(PDO $pdo, string $idToken): array
    {
        $clientId = trim((string) (app_config()['google_oauth']['client_id'] ?? ''));
        if ($clientId === '') {
            throw new RuntimeException('Google sign-in is not configured.');
        }

        return self::findOrCreateStudent($pdo, self::verifyIdToken($idToken, $clientId));
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
                $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(email) = ? LIMIT 2 FOR UPDATE');
                $stmt->execute([$email]);
                $matches = $stmt->fetchAll();
                if (count($matches) > 1) {
                    throw new RuntimeException('This email matches multiple accounts. Contact the scholarship office.');
                }
                $user = $matches[0] ?? null;
            }

            if (!$user) {
                $stmt = $pdo->prepare(
                    'SELECT u.* FROM users u JOIN scholars s ON s.user_id = u.id
                     WHERE LOWER(s.email) = ? LIMIT 2 FOR UPDATE'
                );
                $stmt->execute([$email]);
                $matches = $stmt->fetchAll();
                if (count($matches) > 1) {
                    throw new RuntimeException('This email matches multiple scholar profiles. Contact the scholarship office.');
                }
                $user = $matches[0] ?? null;
            }

            if ($user) {
                if ($user['role'] !== 'student' || $user['status'] !== 'active') {
                    throw new RuntimeException('This Google account cannot access the student app.');
                }
                if (!empty($user['google_sub']) && !hash_equals((string) $user['google_sub'], $subject)) {
                    throw new RuntimeException('This email is linked to another Google account. Contact the scholarship office.');
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

    private static function verifyIdToken(string $token, string $clientId): array
    {
        if ($token === '' || strlen($token) > 10000) {
            throw new RuntimeException('Google identity token is invalid.');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Google identity token is invalid.');
        }

        [$encodedHeader, $encodedClaims, $encodedSignature] = $parts;
        $header = json_decode(self::base64UrlDecode($encodedHeader), true);
        $claims = json_decode(self::base64UrlDecode($encodedClaims), true);
        $signature = self::base64UrlDecode($encodedSignature);
        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
            throw new RuntimeException('Google identity token is unsupported.');
        }

        $certificate = self::googleCertificates()[$header['kid']] ?? null;
        if (!is_string($certificate)
            || openssl_verify($encodedHeader . '.' . $encodedClaims, $signature, $certificate, OPENSSL_ALGO_SHA256) !== 1) {
            throw new RuntimeException('Google identity token signature could not be verified.');
        }

        $now = time();
        $issuer = (string) ($claims['iss'] ?? '');
        $emailVerified = in_array($claims['email_verified'] ?? false, [true, 1, '1', 'true'], true);
        if (!in_array($clientId, (array) ($claims['aud'] ?? []), true)
            || !in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
            || (int) ($claims['exp'] ?? 0) <= $now
            || (int) ($claims['iat'] ?? 0) > $now + 60
            || !$emailVerified
            || empty($claims['email'])
            || !filter_var($claims['email'], FILTER_VALIDATE_EMAIL)
            || empty($claims['sub'])) {
            throw new RuntimeException('Google account verification failed.');
        }

        return $claims;
    }

    private static function googleCertificates(): array
    {
        $curl = curl_init('https://www.googleapis.com/oauth2/v1/certs');
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
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $certificates = is_string($body) ? json_decode($body, true) : null;
        if ($status < 200 || $status >= 300 || !is_array($certificates) || $certificates === []) {
            throw new RuntimeException('Google identity verification is temporarily unavailable.');
        }
        return $certificates;
    }

    private static function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if (!is_string($decoded)) {
            throw new RuntimeException('Google identity token is invalid.');
        }
        return $decoded;
    }
}