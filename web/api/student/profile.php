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

$programs = $pdo->prepare(
    'SELECT p.name FROM enrollments e JOIN scholarship_programs p ON p.id = e.program_id
     WHERE e.scholar_id = ? AND e.status = "active"'
);
$programs->execute([$scholar['id']]);

json_response([
    'success' => true,
    'profile' => [
        'student_no' => $scholar['student_no'],
        'full_name' => scholar_full_name($scholar),
        'course' => $scholar['course'],
        'year_level' => $scholar['year_level'],
        'email' => $scholar['email'],
        'status' => $scholar['status'],
        'programs' => array_column($programs->fetchAll(), 'name'),
        'photo_url' => scholar_photo_url($scholar['photo_path'] ?? null),
        'profile_qr' => profile_qr_payload($scholar),
    ],
]);
