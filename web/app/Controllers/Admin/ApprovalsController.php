<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\ApprovalService;
use PDO;

final class ApprovalsController
{
    public static function handle(PDO $pdo): void
    {
        $reviewer = require_role($pdo, ['approver']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf_token();
            $requestId = (int) ($_POST['request_id'] ?? 0);
            $requestStmt = $pdo->prepare('SELECT request_type, payload FROM approval_requests WHERE id = ? AND status = "pending"');
            $requestStmt->execute([$requestId]);
            $requestBeforeReview = $requestStmt->fetch() ?: null;
            try {
                $entityId = ApprovalService::review(
                    $pdo,
                    $requestId,
                    (int) $reviewer['id'],
                    (string) ($_POST['decision'] ?? ''),
                    trim((string) ($_POST['review_note'] ?? ''))
                );
                flash('success', 'Request reviewed. The decision and reviewer are recorded.');
                $requestPayload = $requestBeforeReview ? (json_decode($requestBeforeReview['payload'], true) ?: []) : [];
                if (($_POST['decision'] ?? '') === 'approved' && ($requestBeforeReview['request_type'] ?? '') === 'batch_create' && !empty($requestPayload['print_after_approval']) && $entityId) {
                    redirect('admin/print-vouchers.php?id=' . $entityId);
                }
            } catch (\Throwable $error) {
                flash('error', $error->getMessage());
            }
            redirect('admin/approvals.php');
        }

        $requests = $pdo->query(
            'SELECT a.*, u.username AS submitter_name
             FROM approval_requests a
             JOIN users u ON u.id = a.submitted_by
             WHERE a.status = "pending"
             ORDER BY a.created_at ASC, a.id ASC'
        )->fetchAll() ?: [];

        foreach ($requests as &$request) {
            $request['request_payload'] = json_decode($request['payload'], true) ?: [];
            $payload = $request['request_payload'];
                $request['scholar_context'] = null;
                $request['program_names'] = [];
                if ($request['request_type'] === 'scholar_enrollment_update') {
                    $scholar = $pdo->prepare('SELECT student_no, first_name, last_name FROM scholars WHERE id = ?');
                    $scholar->execute([(int) $request['entity_id']]);
                    $request['scholar_context'] = $scholar->fetch() ?: null;
                    $programIds = array_values(array_unique(array_map('intval', $payload['program_ids'] ?? [])));
                    if ($programIds !== []) {
                        $placeholders = implode(',', array_fill(0, count($programIds), '?'));
                        $programs = $pdo->prepare("SELECT name FROM scholarship_programs WHERE id IN ($placeholders) ORDER BY name");
                        $programs->execute($programIds);
                        $request['program_names'] = array_column($programs->fetchAll(), 'name');
                    }
                }
                $programId = $request['request_type'] === 'scholar_enrollment_update'
                    ? 0
                    : (int) ($payload['program_id'] ?? $request['entity_id'] ?? 0);
            if ($programId > 0) {
                $program = $pdo->prepare('SELECT name, amount, total_budget FROM scholarship_programs WHERE id = ?');
                $program->execute([$programId]);
                $request['program_context'] = $program->fetch() ?: null;
                if ($request['program_context'] && $request['request_type'] === 'batch_create') {
                    $committed = $pdo->prepare(
                        'SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v
                         JOIN distribution_batches b ON b.id = v.batch_id
                         WHERE b.program_id = ? AND v.status IN ("pending", "claimed")'
                    );
                    $committed->execute([$programId]);
                    $eligible = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE program_id = ? AND status = "active"');
                    $eligible->execute([$programId]);
                    $request['program_context']['current_commitment'] = (float) $committed->fetchColumn();
                    $request['program_context']['eligible_count'] = (int) $eligible->fetchColumn();
                    $request['program_context']['estimated_voucher_amount'] = !empty($payload['generate_vouchers'])
                        ? $request['program_context']['eligible_count'] * (float) $request['program_context']['amount']
                        : 0.0;
                }
            } else {
                $request['program_context'] = null;
            }
        }
        unset($request);

        render_approver_layout($pdo, 'approvals', 'Approval Queue', static function () use ($requests): void {
            view('admin.approvals/index', ['requests' => $requests]);
        });
    }
}