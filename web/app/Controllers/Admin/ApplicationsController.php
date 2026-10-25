<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\ApprovalService;
use App\Services\ApplicationDocumentService;
use PDO;
use PDOException;
use Throwable;

final class ApplicationsController
{
    private const REVIEW_STATUSES = ['under_review', 'needs_info', 'approved', 'declined'];

    public static function handle(PDO $pdo): void
    {
        $reviewer = require_role($pdo, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf_token();
            if (($_POST['action'] ?? '') === 'create_scholar') {
                self::createScholarFromApplication($pdo, $reviewer);
            }

            $applicationId = (int) ($_POST['application_id'] ?? 0);
            $status = (string) ($_POST['status'] ?? '');
            $reviewNote = trim((string) ($_POST['review_note'] ?? ''));

            if ($applicationId < 1 || !in_array($status, self::REVIEW_STATUSES, true) || strlen($reviewNote) > 1000) {
                flash('error', 'Choose a valid review status and keep notes within 1,000 characters.');
                redirect('admin/applications.php');
            }
            if ($status === 'needs_info' && $reviewNote === '') {
                flash('error', 'Add a note explaining what information the student needs to provide.');
                redirect('admin/applications.php');
            }
            if ($status === 'approved') {
                self::createScholarFromApplication($pdo, $reviewer, true, $reviewNote);
            }

            $stmt = $pdo->prepare(
                'SELECT a.id, a.photo_2x2_path, a.enrollment_form_path, s.id AS scholar_id FROM scholarship_applications a
                 LEFT JOIN scholars s ON s.user_id = a.applicant_user_id WHERE a.id = ?'
            );
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application) {
                flash('error', 'Application not found.');
                redirect('admin/applications.php');
            }
            if ($application['scholar_id']) {
                flash('error', 'This application already has a scholar profile. Its decision can no longer be changed here.');
                redirect('admin/applications.php');
            }
            if ($status === 'approved'
                && (ApplicationDocumentService::path((string) ($application['photo_2x2_path'] ?? '')) === null
                    || ApplicationDocumentService::path((string) ($application['enrollment_form_path'] ?? '')) === null)) {
                flash('error', 'Both the 2x2 photo and enrollment form must be present before approving this application. Request the missing documents first.');
                redirect('admin/applications.php');
            }

            $pdo->prepare(
                'UPDATE scholarship_applications
                 SET status = ?, review_note = ?, reviewed_by = ?, reviewed_at = NOW()
                 WHERE id = ?'
            )->execute([$status, $reviewNote ?: null, (int) $reviewer['id'], $applicationId]);
            audit_log($pdo, (int) $reviewer['id'], 'application_reviewed', 'scholarship_application', $applicationId, 'Application status set to ' . $status);
            flash('success', 'Application review saved. Approval does not create an enrollment or voucher.');
            redirect('admin/applications.php');
        }

        $validStatuses = ['submitted', 'under_review', 'needs_info', 'approved', 'declined'];
        $statusFilter = (string) ($_GET['status'] ?? '');
        if (!in_array($statusFilter, $validStatuses, true)) {
            $statusFilter = '';
        }
            $applicationSql =
            'SELECT a.*, u.email, COALESCE(p.name, "General scholar registration") AS program_name,
                p.amount, p.academic_year, p.semester,
                    s.id AS scholar_id
             FROM scholarship_applications a
             JOIN users u ON u.id = a.applicant_user_id
             LEFT JOIN scholarship_programs p ON p.id = a.program_id
             LEFT JOIN scholars s ON s.user_id = a.applicant_user_id' .
            ($statusFilter !== '' ? ' WHERE a.status = ?' : '') .
            ' ORDER BY FIELD(a.status, "submitted", "under_review", "needs_info", "approved", "declined"), a.created_at ASC';
        $applicationQuery = $pdo->prepare($applicationSql);
        $applicationQuery->execute($statusFilter !== '' ? [$statusFilter] : []);
        $applications = $applicationQuery->fetchAll();
        $counts = $pdo->query('SELECT status, COUNT(*) AS total FROM scholarship_applications GROUP BY status')->fetchAll();
        $statusCounts = array_fill_keys(['submitted', 'under_review', 'needs_info', 'approved', 'declined'], 0);
        foreach ($counts as $row) {
            $statusCounts[$row['status']] = (int) $row['total'];
        }

        render_admin_page($pdo, 'applications', 'Student Applications', 'admin/applications/index', [
            'applications' => $applications,
            'statusCounts' => $statusCounts,
            'statusFilter' => $statusFilter,
        ], ['applications' => true, 'datatables' => true]);
    }

    private static function createScholarFromApplication(PDO $pdo, array $reviewer, bool $approveApplication = false, string $reviewNote = ''): never
    {
        $applicationId = (int) ($_POST['application_id'] ?? 0);
        $studentNo = trim((string) ($_POST['student_no'] ?? ''));
        if ($applicationId < 1 || $studentNo === '' || strlen($studentNo) > 32) {
            flash('error', 'Enter a valid student number (1 to 32 characters).');
            redirect('admin/applications.php');
        }

        $profilePhotoPath = null;
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'SELECT a.*, u.role AS applicant_role, u.status AS applicant_status, u.email,
                           p.status AS program_status
                 FROM scholarship_applications a
                 JOIN users u ON u.id = a.applicant_user_id
                       LEFT JOIN scholarship_programs p ON p.id = a.program_id
                 WHERE a.id = ? FOR UPDATE'
            );
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application || (!$approveApplication && $application['status'] !== 'approved')) {
                throw new \RuntimeException('Only an approved registration can create a scholar profile.');
            }
            if ($application['applicant_role'] !== 'student' || $application['applicant_status'] !== 'active') {
                throw new \RuntimeException('The applicant account is not active.');
            }
            if (!empty($application['program_id']) && $application['program_status'] !== 'active') {
                throw new \RuntimeException('The selected program is inactive. Activate it before creating a scholar profile.');
            }
            if (ApplicationDocumentService::path((string) ($application['photo_2x2_path'] ?? '')) === null
                || ApplicationDocumentService::path((string) ($application['enrollment_form_path'] ?? '')) === null) {
                throw new \RuntimeException('Both the 2x2 photo and enrollment form are required before creating a scholar profile.');
            }

            $existingScholar = $pdo->prepare('SELECT id FROM scholars WHERE user_id = ? FOR UPDATE');
            $existingScholar->execute([(int) $application['applicant_user_id']]);
            if ($existingScholar->fetchColumn()) {
                throw new \RuntimeException('This student already has a scholar profile.');
            }
            $existingStudentNo = $pdo->prepare('SELECT id FROM scholars WHERE student_no = ?');
            $existingStudentNo->execute([$studentNo]);
            if ($existingStudentNo->fetchColumn()) {
                throw new \RuntimeException('That student number is already in use.');
            }

            $publicId = generate_public_id($pdo);
            $qrToken = generate_token(32);
            $pdo->prepare(
                'INSERT INTO scholars
                 (user_id, student_no, first_name, last_name, course, year_level, email, phone, photo_path, qr_token, public_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, "active")'
            )->execute([
                (int) $application['applicant_user_id'],
                $studentNo,
                $application['first_name'],
                $application['last_name'],
                $application['course'],
                $application['year_level'],
                $application['email'],
                $application['phone'],
                $qrToken,
                $publicId,
            ]);
            $scholarId = (int) $pdo->lastInsertId();
            $profilePhotoPath = ApplicationDocumentService::copyToScholarPhoto((string) $application['photo_2x2_path'], $scholarId);
            $pdo->prepare('UPDATE scholars SET photo_path = ? WHERE id = ?')->execute([$profilePhotoPath, $scholarId]);

            if (!empty($application['program_id'])) {
                ApprovalService::submit(
                    $pdo,
                    'scholar_enrollment_update',
                    ['program_ids' => [(int) $application['program_id']]],
                    (int) $reviewer['id'],
                    $scholarId
                );
            }
            if ($approveApplication) {
                $pdo->prepare('UPDATE scholarship_applications SET status = "approved", review_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?')
                    ->execute([$reviewNote !== '' ? $reviewNote : null, (int) $reviewer['id'], $applicationId]);
            }
            $auditDetails = 'Scholar profile created with 2x2 photo';
            $auditDetails .= !empty($application['program_id']) ? '; program enrollment submitted for approval' : '; no program assigned yet';
            if ($approveApplication) {
                $auditDetails = 'Registration approved; ' . $auditDetails;
            }
            audit_log($pdo, (int) $reviewer['id'], 'application_converted_to_scholar', 'scholarship_application', $applicationId, $auditDetails);
            $pdo->commit();
            $message = $approveApplication
                ? 'Registration approved and scholar profile created with the submitted 2x2 photo.'
                : 'Scholar profile created with the submitted 2x2 photo.';
            $message .= !empty($application['program_id'])
                ? ' Program enrollment is waiting for the approver; no voucher was issued.'
                : ' Program assignment can be requested later; no enrollment or voucher was created.';
            flash('success', $message);
            redirect('admin/scholar-view.php?id=' . $scholarId);
        } catch (PDOException $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            delete_scholar_photo_file($profilePhotoPath);
            error_log('Application conversion failed: ' . $error->getMessage());
            flash('error', $error->getCode() === '23000' ? 'The student number or profile is already in use.' : 'Could not create the scholar profile. Please try again.');
            redirect('admin/applications.php');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            delete_scholar_photo_file($profilePhotoPath);
            flash('error', $error->getMessage());
            redirect('admin/applications.php');
        }
    }
}