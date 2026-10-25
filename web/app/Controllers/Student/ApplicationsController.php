<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Services\ApplicationDocumentService;
use PDO;
use PDOException;
use Throwable;

final class ApplicationsController
{
    public static function handle(PDO $pdo): void
    {
        $user = require_role($pdo, ['student']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf_token();
            $applicationId = (int) ($_POST['application_id'] ?? 0);
            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $schoolName = trim((string) ($_POST['school_name'] ?? ''));
            $course = trim((string) ($_POST['course'] ?? ''));
            $yearLevel = trim((string) ($_POST['year_level'] ?? ''));
            $applicationNote = trim((string) ($_POST['application_note'] ?? ''));
            $privacyAcknowledged = ($_POST['privacy_acknowledged'] ?? '') === '1';
            $existingApplication = null;

            if ($applicationId > 0) {
                $stmt = $pdo->prepare(
                    'SELECT program_id, photo_2x2_path, enrollment_form_path FROM scholarship_applications
                     WHERE id = ? AND applicant_user_id = ? AND status = "needs_info"'
                );
                $stmt->execute([$applicationId, (int) $user['id']]);
                $existingApplication = $stmt->fetch() ?: null;
                if (!$existingApplication) {
                    flash('error', 'This application cannot be edited.');
                    redirect('student/applications.php');
                }
            }

            if ($firstName === '' || $lastName === '' || $phone === '' || $schoolName === '' || $course === '' || $yearLevel === '' || !$privacyAcknowledged) {
                flash('error', 'Complete all required details and acknowledge how your application information will be used.');
                redirect($applicationId > 0 ? 'student/applications.php?edit=' . $applicationId : 'student/applications.php');
            }
            if (strlen($applicationNote) > 2000) {
                flash('error', 'The application note must be 2,000 characters or fewer.');
                redirect($applicationId > 0 ? 'student/applications.php?edit=' . $applicationId : 'student/applications.php');
            }

            $newFiles = [];
            $replacedFiles = [];
            try {
                $photoPath = ApplicationDocumentService::store(
                    $_FILES['photo_2x2'] ?? null,
                    'photo_2x2',
                    !$existingApplication || empty($existingApplication['photo_2x2_path'])
                );
                if ($photoPath !== null) {
                    $newFiles[] = $photoPath;
                }
                $formPath = ApplicationDocumentService::store(
                    $_FILES['enrollment_form'] ?? null,
                    'enrollment_form',
                    !$existingApplication || empty($existingApplication['enrollment_form_path'])
                );
                if ($formPath !== null) {
                    $newFiles[] = $formPath;
                }

                $pdo->beginTransaction();
                if ($applicationId > 0) {
                    $pdo->prepare(
                        'UPDATE scholarship_applications
                         SET first_name = ?, last_name = ?, phone = ?, school_name = ?, course = ?, year_level = ?,
                             application_note = ?, privacy_acknowledged_at = NOW(), status = "submitted",
                             photo_2x2_path = ?, enrollment_form_path = ?, review_note = NULL, reviewed_by = NULL, reviewed_at = NULL
                         WHERE id = ? AND applicant_user_id = ? AND status = "needs_info"'
                    )->execute([
                        $firstName, $lastName, $phone, $schoolName, $course, $yearLevel, $applicationNote ?: null,
                        $photoPath ?? $existingApplication['photo_2x2_path'],
                        $formPath ?? $existingApplication['enrollment_form_path'],
                        $applicationId, (int) $user['id'],
                    ]);
                    if ($photoPath !== null && !empty($existingApplication['photo_2x2_path'])) {
                        $replacedFiles[] = $existingApplication['photo_2x2_path'];
                    }
                    if ($formPath !== null && !empty($existingApplication['enrollment_form_path'])) {
                        $replacedFiles[] = $existingApplication['enrollment_form_path'];
                    }
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

                audit_log($pdo, (int) $user['id'], $action, 'scholarship_application', $savedId, 'Student submitted scholarship application with required documents');
                $pdo->commit();
                foreach ($replacedFiles as $replacedFile) {
                    ApplicationDocumentService::delete($replacedFile);
                }
                flash('success', 'Your application was submitted to the SK office for review.');
            } catch (PDOException $error) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($newFiles as $newFile) {
                    ApplicationDocumentService::delete($newFile);
                }
                if ($error->getCode() === '23000') {
                    flash('error', 'You already have a scholar registration or application on file.');
                } else {
                    error_log('Student application save failed: ' . $error->getMessage());
                    flash('error', 'We could not save your application. Please try again.');
                }
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($newFiles as $newFile) {
                    ApplicationDocumentService::delete($newFile);
                }
                flash('error', $error instanceof \RuntimeException ? $error->getMessage() : 'We could not save your application. Please try again.');
            }
            redirect('student/applications.php');
        }

        $editing = null;
        $editId = (int) ($_GET['edit'] ?? 0);
        if ($editId > 0) {
            $stmt = $pdo->prepare(
                'SELECT a.*, p.name AS program_name FROM scholarship_applications a
                 LEFT JOIN scholarship_programs p ON p.id = a.program_id
                 WHERE a.id = ? AND a.applicant_user_id = ? AND a.status = "needs_info"'
            );
            $stmt->execute([$editId, (int) $user['id']]);
            $editing = $stmt->fetch() ?: null;
        }

        $applications = $pdo->prepare(
            'SELECT a.*, COALESCE(p.name, "General scholar registration") AS program_name, p.amount, p.academic_year, p.semester,
                    s.id AS scholar_id
             FROM scholarship_applications a LEFT JOIN scholarship_programs p ON p.id = a.program_id
             LEFT JOIN scholars s ON s.user_id = a.applicant_user_id
             WHERE a.applicant_user_id = ? ORDER BY a.created_at DESC'
        );
        $applications->execute([(int) $user['id']]);

        render_public_layout('Your scholar registration', static function () use ($user, $editing, $applications): void {
            view('student.applications', [
                'user' => $user,
                'editing' => $editing,
                'applications' => $applications->fetchAll(),
            ]);
        });
    }
}