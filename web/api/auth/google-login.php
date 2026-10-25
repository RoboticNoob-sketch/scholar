<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$idToken = trim((string) ($body['id_token'] ?? ''));
if ($idToken === '') {
    json_response(['success' => false, 'error' => 'Google identity token is required'], 422);
}

try {
    $user = App\Services\GoogleIdentityService::signIn($pdo, $idToken);
} catch (Throwable $error) {
    error_log('Mobile Google login failed: ' . $error->getMessage());
    $configured = trim((string) (app_config()['google_oauth']['client_id'] ?? '')) !== '';
    json_response([
        'success' => false,
        'error' => $configured
            ? 'Google sign-in failed. Check the selected account or contact the scholarship office.'
            : 'Google sign-in is not configured on this server.',
    ], $configured ? 401 : 503);
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
            'photo_url' => scholar_photo_url($profile['photo_path'] ?? null, (int) $profile['id']),
        ];
    }
}

audit_log($pdo, (int) $user['id'], 'google_mobile_login', 'user', (int) $user['id'], 'Student signed in with Google on mobile');
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