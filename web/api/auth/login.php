<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND role IN ("student", "staff") AND status = "active"');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    audit_log($pdo, null, 'login_failed', 'user', null, 'Mobile login failed for: ' . $username);
    json_response(['success' => false, 'error' => 'Invalid credentials'], 401);
}

$token = create_api_token($pdo, (int) $user['id']);

$scholarData = null;
if ($user['role'] === 'student') {
    $scholar = $pdo->prepare('SELECT * FROM scholars WHERE user_id = ?');
    $scholar->execute([$user['id']]);
    $profile = $scholar->fetch();
    if ($profile) {
        $scholarData = [
            'id' => (int) $profile['id'],
            'student_no' => $profile['student_no'],
            'first_name' => $profile['first_name'],
            'last_name' => $profile['last_name'],
            'full_name' => scholar_full_name($profile),
            'course' => $profile['course'],
            'year_level' => $profile['year_level'],
            'email' => $profile['email'],
        ];
    }
}

audit_log($pdo, (int) $user['id'], 'mobile_login', 'user', (int) $user['id'], ucfirst($user['role']) . ' mobile login');

json_response([
    'success' => true,
    'token' => $token,
    'role' => $user['role'],
    'user' => [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ],
    'scholar' => $scholarData,
]);
