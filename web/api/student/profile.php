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
    $application = $pdo->prepare(
        'SELECT a.first_name, a.last_name, a.course, a.year_level, a.status, a.photo_2x2_path, u.email
         FROM scholarship_applications a JOIN users u ON u.id = a.applicant_user_id
         WHERE a.applicant_user_id = ? ORDER BY a.updated_at DESC, a.id DESC LIMIT 1'
    );
    $application->execute([(int) $user['id']]);
    $applicant = $application->fetch();
    if (!$applicant) {
        json_response(['success' => false, 'error' => 'Profile not found'], 404);
    }

    json_response([
        'success' => true,
        'profile' => [
            'is_scholar' => false,
            'student_no' => null,
            'full_name' => scholar_full_name($applicant),
            'course' => $applicant['course'],
            'year_level' => $applicant['year_level'],
            'email' => $applicant['email'],
            'status' => $applicant['status'],
            'programs' => [],
            'photo_url' => !empty($applicant['photo_2x2_path']) ? absolute_url('api/student/application-photo.php') : null,
            'profile_qr' => null,
        ],
    ]);
}

$programs = $pdo->prepare(
    'SELECT p.name FROM enrollments e JOIN scholarship_programs p ON p.id = e.program_id
     WHERE e.scholar_id = ? AND e.status = "active"'
);
$programs->execute([$scholar['id']]);

json_response([
    'success' => true,
    'profile' => [
        'is_scholar' => true,
        'student_no' => $scholar['student_no'],
        'full_name' => scholar_full_name($scholar),
        'course' => $scholar['course'],
        'year_level' => $scholar['year_level'],
        'email' => $scholar['email'],
        'status' => $scholar['status'],
        'programs' => array_column($programs->fetchAll(), 'name'),
        'photo_url' => scholar_photo_url($scholar['photo_path'] ?? null, (int) $scholar['id']),
        'profile_qr' => profile_qr_payload($scholar),
    ],
]);
