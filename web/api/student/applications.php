<?php

declare(strict_types=1);

use App\Services\ApplicationDocumentService;

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = authenticate_api_token($pdo, bearer_token());
if (!$user || $user['role'] !== 'student') {
    json_response(['success' => false, 'error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $applicationsQuery = $pdo->prepare(
        'SELECT a.*, COALESCE(p.name, "General scholar registration") AS program_name,
                p.amount, p.academic_year, p.semester, s.id AS scholar_id
         FROM scholarship_applications a
         LEFT JOIN scholarship_programs p ON p.id = a.program_id
         LEFT JOIN scholars s ON s.user_id = a.applicant_user_id
         WHERE a.applicant_user_id = ?
         ORDER BY a.created_at DESC'
    );
    $applicationsQuery->execute([(int) $user['id']]);
    $applications = [];
    foreach ($applicationsQuery->fetchAll() as $row) {
        $applications[] = [
            'id' => (int) $row['id'],
            'program_id' => $row['program_id'] === null ? null : (int) $row['program_id'],
            'program_name' => $row['program_name'],
            'amount' => $row['amount'] === null ? null : (float) $row['amount'],
            'academic_year' => $row['academic_year'],
            'semester' => $row['semester'],
            'status' => $row['status'],
            'review_note' => $row['review_note'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'phone' => $row['phone'],
            'school_name' => $row['school_name'],
            'course' => $row['course'],
            'year_level' => $row['year_level'],
            'application_note' => $row['application_note'],
            'has_photo' => !empty($row['photo_2x2_path']),
            'has_enrollment_form' => !empty($row['enrollment_form_path']),
            'scholar_id' => $row['scholar_id'] === null ? null : (int) $row['scholar_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'reviewed_at' => $row['reviewed_at'],
        ];
    }

    $updates = [];
    if ($applications !== []) {
        $ids = array_column($applications, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $eventsQuery = $pdo->prepare(
            'SELECT id, entity_id AS application_id, action, details, created_at
             FROM audit_logs
             WHERE entity_type = "scholarship_application"
               AND action IN ("application_submitted", "application_resubmitted", "application_reviewed", "application_converted_to_scholar")
               AND entity_id IN (' . $placeholders . ')
             ORDER BY id DESC LIMIT 50'
        );
        $eventsQuery->execute($ids);
        foreach ($eventsQuery->fetchAll() as $event) {
            $eventStatus = null;
            if (preg_match('/Application status set to (submitted|under_review|needs_info|approved|declined)/', (string) $event['details'], $matches)) {
                $eventStatus = $matches[1];
            }
            $updates[] = [
                'id' => (int) $event['id'],
                'application_id' => (int) $event['application_id'],
                'action' => $event['action'],
                'status' => $eventStatus,
                'created_at' => $event['created_at'],
            ];
        }
    }

    $scholarQuery = $pdo->prepare('SELECT id FROM scholars WHERE user_id = ? LIMIT 1');
    $scholarQuery->execute([(int) $user['id']]);
    $hasScholar = (bool) $scholarQuery->fetchColumn();
    $hasGeneralRegistration = false;
    foreach ($applications as $application) {
        if ($application['program_id'] === null) {
            $hasGeneralRegistration = true;
            break;
        }
    }

    json_response([
        'success' => true,
        'applications' => $applications,
        'updates' => $updates,
        'has_scholar' => $hasScholar,
        'can_apply' => !$hasScholar && !$hasGeneralRegistration,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$applicationId = (int) ($_POST['application_id'] ?? 0);
$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$schoolName = trim((string) ($_POST['school_name'] ?? ''));
$course = trim((string) ($_POST['course'] ?? ''));
$yearLevel = trim((string) ($_POST['year_level'] ?? ''));
$applicationNote = trim((string) ($_POST['application_note'] ?? ''));
$privacyAcknowledged = ($_POST['privacy_acknowledged'] ?? '') === '1';

if ($firstName === '' || $lastName === '' || $phone === '' || $schoolName === '' || $course === '' || $yearLevel === '' || !$privacyAcknowledged) {
    json_response(['success' => false, 'error' => 'Complete all required details and acknowledge the privacy notice.'], 422);
}
if (strlen($applicationNote) > 2000) {
    json_response(['success' => false, 'error' => 'The application note must be 2,000 characters or fewer.'], 422);
}

$existing = null;
if ($applicationId > 0) {
    $existingQuery = $pdo->prepare(
        'SELECT id, program_id, photo_2x2_path, enrollment_form_path
         FROM scholarship_applications
         WHERE id = ? AND applicant_user_id = ? AND status = "needs_info"'
    );
    $existingQuery->execute([$applicationId, (int) $user['id']]);
    $existing = $existingQuery->fetch() ?: null;
    if (!$existing) {
        json_response(['success' => false, 'error' => 'This application cannot be edited.'], 409);
    }
} else {
    $scholarQuery = $pdo->prepare('SELECT id FROM scholars WHERE user_id = ? LIMIT 1');
    $scholarQuery->execute([(int) $user['id']]);
    if ($scholarQuery->fetchColumn()) {
        json_response(['success' => false, 'error' => 'Your scholar profile is already on file.'], 409);
    }
    $applicationQuery = $pdo->prepare('SELECT id FROM scholarship_applications WHERE applicant_user_id = ? AND program_id IS NULL LIMIT 1');
    $applicationQuery->execute([(int) $user['id']]);
    if ($applicationQuery->fetchColumn()) {
        json_response(['success' => false, 'error' => 'Your one-time registration is already on file.'], 409);
    }
}

$newFiles = [];
$replacedFiles = [];
try {
    $photoPath = ApplicationDocumentService::store(
        $_FILES['photo_2x2'] ?? null,
        'photo_2x2',
        !$existing || empty($existing['photo_2x2_path'])
    );
    if ($photoPath !== null) $newFiles[] = $photoPath;
    $formPath = ApplicationDocumentService::store(
        $_FILES['enrollment_form'] ?? null,
        'enrollment_form',
        !$existing || empty($existing['enrollment_form_path'])
    );
    if ($formPath !== null) $newFiles[] = $formPath;

    $pdo->beginTransaction();
    if ($existing) {
        $pdo->prepare(
            'UPDATE scholarship_applications
             SET first_name = ?, last_name = ?, phone = ?, school_name = ?, course = ?, year_level = ?,
                 application_note = ?, privacy_acknowledged_at = NOW(), status = "submitted",
                 photo_2x2_path = ?, enrollment_form_path = ?, review_note = NULL, reviewed_by = NULL, reviewed_at = NULL
             WHERE id = ? AND applicant_user_id = ? AND status = "needs_info"'
        )->execute([
            $firstName, $lastName, $phone, $schoolName, $course, $yearLevel, $applicationNote ?: null,
            $photoPath ?? $existing['photo_2x2_path'], $formPath ?? $existing['enrollment_form_path'],
            $applicationId, (int) $user['id'],
        ]);
        if ($photoPath !== null && !empty($existing['photo_2x2_path'])) $replacedFiles[] = $existing['photo_2x2_path'];
        if ($formPath !== null && !empty($existing['enrollment_form_path'])) $replacedFiles[] = $existing['enrollment_form_path'];
        $savedId = $applicationId;
        $action = 'application_resubmitted';
    } else {
        $pdo->prepare(
            'INSERT INTO scholarship_applications
             (applicant_user_id, program_id, first_name, last_name, phone, school_name, course, year_level,
              application_note, photo_2x2_path, enrollment_form_path, privacy_acknowledged_at)
             VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            (int) $user['id'], $firstName, $lastName, $phone, $schoolName, $course, $yearLevel,
            $applicationNote ?: null, $photoPath, $formPath,
        ]);
        $savedId = (int) $pdo->lastInsertId();
        $action = 'application_submitted';
    }
    audit_log($pdo, (int) $user['id'], $action, 'scholarship_application', $savedId, 'Student submitted scholar registration with required documents');
    $pdo->commit();

    foreach ($replacedFiles as $replacedFile) ApplicationDocumentService::delete($replacedFile);
    json_response(['success' => true, 'application_id' => $savedId, 'message' => 'Your registration was sent to the scholarship office.']);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($newFiles as $newFile) ApplicationDocumentService::delete($newFile);
    error_log('Mobile application submission failed: ' . $error->getMessage());
    $status = $error instanceof PDOException && $error->getCode() === '23000' ? 409 : 422;
    $message = $error instanceof RuntimeException
        ? $error->getMessage()
        : ($status === 409 ? 'Your registration is already on file.' : 'Could not save the registration. Please try again.');
    json_response(['success' => false, 'error' => $message], $status);
}
