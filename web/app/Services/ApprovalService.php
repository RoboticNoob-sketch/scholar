<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

final class ApprovalService
{
    public static function submit(PDO $pdo, string $type, array $payload, int $submitterId, ?int $entityId = null): int
    {
        $approverCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "approver" AND status = "active"')->fetchColumn();
        if ($approverCount === 0) {
            throw new RuntimeException('Create an active approver account before submitting approval requests.');
        }
        if (!in_array($type, ['program_create', 'program_update', 'batch_create', 'scholar_enrollment_update'], true)) {
            throw new RuntimeException('Unsupported approval request.');
        }
        if (in_array($type, ['program_create', 'program_update'], true)) {
            self::validateProgramPayload($payload);
            self::validateScholarIds($pdo, $payload['scholar_ids'] ?? []);
        }
        if ($type === 'program_update') {
            $existing = $pdo->prepare('SELECT id FROM approval_requests WHERE request_type = ? AND entity_id = ? AND status = "pending" LIMIT 1');
            $existing->execute([$type, $entityId]);
            if ($existing->fetchColumn()) throw new RuntimeException('A change request for this program is already awaiting approval.');
            $program = $pdo->prepare('SELECT id FROM scholarship_programs WHERE id = ?');
            $program->execute([$entityId]);
            if (!$program->fetchColumn()) throw new RuntimeException('Program not found.');
        }
        if ($type === 'batch_create') self::validateBatchPayload($pdo, $payload);
        if ($type === 'scholar_enrollment_update') {
            if (!$entityId || !self::isActiveScholar($pdo, $entityId)) throw new RuntimeException('Choose an active scholar for this enrollment request.');
            self::validateProgramIds($pdo, $payload['program_ids'] ?? []);
            $pending = $pdo->prepare('SELECT id FROM approval_requests WHERE request_type = ? AND entity_id = ? AND status = "pending" LIMIT 1');
            $pending->execute([$type, $entityId]);
            if ($pending->fetchColumn()) throw new RuntimeException('An enrollment request for this scholar is already awaiting approval.');
        }

        $stmt = $pdo->prepare('INSERT INTO approval_requests (request_type, entity_id, payload, submitted_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$type, $entityId, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $submitterId]);
        $requestId = (int) $pdo->lastInsertId();
        audit_log($pdo, $submitterId, 'approval_submitted', $type, $entityId, 'Approval request #' . $requestId . ' submitted');
        return $requestId;
    }

    public static function review(PDO $pdo, int $requestId, int $reviewerId, string $decision, string $note = ''): ?int
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) throw new RuntimeException('Choose approve or reject.');
        if ($decision === 'rejected' && trim($note) === '') throw new RuntimeException('Add a reason when rejecting a request.');
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM approval_requests WHERE id = ? FOR UPDATE');
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            if (!$request || $request['status'] !== 'pending') throw new RuntimeException('This request has already been reviewed or no longer exists.');
            if ((int) $request['submitted_by'] === $reviewerId) throw new RuntimeException('You cannot approve your own request.');
            $entityId = $request['entity_id'] !== null ? (int) $request['entity_id'] : null;
            if ($decision === 'approved') {
                $entityId = self::applyApprovedRequest($pdo, $request, json_decode($request['payload'], true, 512, JSON_THROW_ON_ERROR));
            }
            $pdo->prepare('UPDATE approval_requests SET status=?, entity_id=?, reviewed_by=?, review_note=?, reviewed_at=NOW() WHERE id=?')
                ->execute([$decision, $entityId, $reviewerId, trim($note) !== '' ? trim($note) : null, $requestId]);
            audit_log($pdo, $reviewerId, 'approval_' . $decision, 'approval_request', $requestId, trim($note) ?: null);
            $pdo->commit();
            return $entityId;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    private static function applyApprovedRequest(PDO $pdo, array $request, array $payload): int
    {
        return match ($request['request_type']) {
            'program_create' => self::createProgram($pdo, $payload),
            'program_update' => self::updateProgram($pdo, (int) $request['entity_id'], $payload),
            'batch_create' => self::createBatch($pdo, (int) $request['submitted_by'], $payload),
            'scholar_enrollment_update' => self::updateScholarEnrollments($pdo, (int) $request['entity_id'], $payload),
            default => throw new RuntimeException('This request type cannot be approved.'),
        };
    }

    private static function createProgram(PDO $pdo, array $payload): int
    {
        self::validateProgramPayload($payload);
        self::validateScholarIds($pdo, $payload['scholar_ids'] ?? []);
        $stmt = $pdo->prepare('INSERT INTO scholarship_programs (name,description,amount,total_budget,academic_year,semester,status) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([trim($payload['name']), trim($payload['description'] ?? ''), (float) $payload['amount'], (float) $payload['total_budget'], trim($payload['academic_year']), trim($payload['semester']), $payload['status']]);
        $id = (int) $pdo->lastInsertId();
        self::saveEnrollments($pdo, $id, $payload['scholar_ids'] ?? []);
        return $id;
    }

    private static function updateProgram(PDO $pdo, int $id, array $payload): int
    {
        self::validateProgramPayload($payload);
        self::validateScholarIds($pdo, $payload['scholar_ids'] ?? []);
        $stmt = $pdo->prepare('SELECT id FROM scholarship_programs WHERE id=? FOR UPDATE');
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) throw new RuntimeException('The program was removed before approval.');
        $committed = self::programCommittedAmount($pdo, $id);
        $additional = self::missingOpenBatchVoucherValue($pdo, $id, $payload['scholar_ids'] ?? [], (float) $payload['amount']);
        if ((float) $payload['total_budget'] < $committed + $additional) throw new RuntimeException('The proposed budget is below current voucher commitments plus vouchers this change would issue.');
        $pdo->prepare('UPDATE scholarship_programs SET name=?,description=?,amount=?,total_budget=?,academic_year=?,semester=?,status=? WHERE id=?')
            ->execute([trim($payload['name']), trim($payload['description'] ?? ''), (float) $payload['amount'], (float) $payload['total_budget'], trim($payload['academic_year']), trim($payload['semester']), $payload['status'], $id]);
        self::saveEnrollments($pdo, $id, $payload['scholar_ids'] ?? []);
        sync_vouchers_for_program($pdo, $id);
        return $id;
    }

    private static function createBatch(PDO $pdo, int $createdBy, array $payload): int
    {
        self::validateBatchPayload($pdo, $payload);
        $programId = (int) $payload['program_id'];
        $stmt = $pdo->prepare('SELECT id,amount,status FROM scholarship_programs WHERE id=? FOR UPDATE');
        $stmt->execute([$programId]);
        $program = $stmt->fetch();
        if (!$program || $program['status'] !== 'active') throw new RuntimeException('The selected program is no longer active.');
        $eligible = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE program_id=? AND status="active"');
        $eligible->execute([$programId]);
        self::ensureBudgetCapacity($pdo, $programId, (int) $eligible->fetchColumn() * (float) $program['amount']);
        $pdo->prepare('INSERT INTO distribution_batches (program_id,name,distribution_date,venue,status,created_by) VALUES (?,?,?,?,"draft",?)')
            ->execute([$programId, trim($payload['name']), $payload['distribution_date'], trim($payload['venue']), $createdBy]);
        $batchId = (int) $pdo->lastInsertId();
        if (!empty($payload['generate_vouchers'])) generate_missing_vouchers_for_batch($pdo, $batchId);
        return $batchId;
    }

    private static function validateProgramPayload(array $payload): void
    {
        foreach (['name','academic_year','semester'] as $field) if (trim((string) ($payload[$field] ?? '')) === '') throw new RuntimeException('Program name, academic year, and semester are required.');
        if ((float) ($payload['amount'] ?? -1) < 0 || (float) ($payload['total_budget'] ?? -1) < 0) throw new RuntimeException('Award amount and total budget cannot be negative.');
        if (!in_array($payload['status'] ?? '', ['active','inactive'], true)) throw new RuntimeException('Invalid program status.');
    }

    private static function validateScholarIds(PDO $pdo, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) return;
        $slots = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM scholars WHERE status='active' AND id IN ($slots)");
        $stmt->execute($ids);
        if ((int) $stmt->fetchColumn() !== count($ids)) throw new RuntimeException('One or more selected scholars are no longer active.');
    }

    private static function validateBatchPayload(PDO $pdo, array $payload): void
    {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.amount, p.total_budget,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.program_id=p.id AND e.status="active") AS enrolled_count,
                    (SELECT COALESCE(SUM(v.amount),0) FROM claim_vouchers v JOIN distribution_batches b ON b.id=v.batch_id WHERE b.program_id=p.id AND v.status IN ("pending","claimed")) AS committed_amount
             FROM scholarship_programs p WHERE p.id=? AND p.status="active"'
        );
        $stmt->execute([(int) ($payload['program_id'] ?? 0)]);
        $program = $stmt->fetch();
        if (!$program) throw new RuntimeException('Choose an active program.');
        $budget = (float) $program['total_budget'];
        $available = $budget - (float) $program['committed_amount'];
        if ($budget <= 0) throw new RuntimeException('This program has no total budget. Set one before requesting a batch.');
        if ($available <= 0) throw new RuntimeException('No budget remains for this program. Increase its budget or release existing commitments.');
        $estimated = (int) $program['enrolled_count'] * (float) $program['amount'];
        if ($estimated > $available) {
            throw new RuntimeException('This batch needs about ' . format_money($estimated) . ' for all enrolled scholars, but only ' . format_money($available) . ' is available.');
        }
        if (trim((string) ($payload['name'] ?? '')) === '' || trim((string) ($payload['venue'] ?? '')) === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($payload['distribution_date'] ?? ''))) throw new RuntimeException('Batch name, date, and venue are required.');
    }

    private static function saveEnrollments(PDO $pdo, int $programId, array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        $pdo->prepare('UPDATE enrollments SET status="removed" WHERE program_id=? AND status="active"')->execute([$programId]);
        $stmt = $pdo->prepare('INSERT INTO enrollments (scholar_id,program_id,status) VALUES (?, ?, "active") ON DUPLICATE KEY UPDATE status="active"');
        foreach ($ids as $id) $stmt->execute([$id, $programId]);
    }

    private static function updateScholarEnrollments(PDO $pdo, int $scholarId, array $payload): int
    {
        if (!self::isActiveScholar($pdo, $scholarId)) throw new RuntimeException('The scholar is no longer active.');
        $programIds = array_values(array_unique(array_filter(array_map('intval', $payload['program_ids'] ?? []), static fn (int $id): bool => $id > 0)));
        self::validateProgramIds($pdo, $programIds);
        sync_enrollments_for_scholar($pdo, $scholarId, $programIds);
        return $scholarId;
    }

    private static function isActiveScholar(PDO $pdo, int $scholarId): bool
    {
        $stmt = $pdo->prepare('SELECT id FROM scholars WHERE id = ? AND status = "active"');
        $stmt->execute([$scholarId]);
        return (bool) $stmt->fetchColumn();
    }

    private static function validateProgramIds(PDO $pdo, array $programIds): void
    {
        $programIds = array_values(array_unique(array_filter(array_map('intval', $programIds), static fn (int $id): bool => $id > 0)));
        if ($programIds === []) return;
        $placeholders = implode(',', array_fill(0, count($programIds), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM scholarship_programs WHERE status = 'active' AND id IN ($placeholders)");
        $stmt->execute($programIds);
        if ((int) $stmt->fetchColumn() !== count($programIds)) throw new RuntimeException('One or more selected programs are no longer active.');
    }

    private static function programCommittedAmount(PDO $pdo, int $programId): float
    {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(v.amount),0) FROM claim_vouchers v JOIN distribution_batches b ON b.id=v.batch_id WHERE b.program_id=? AND v.status IN ("pending","claimed")');
        $stmt->execute([$programId]);
        return (float) $stmt->fetchColumn();
    }

    private static function ensureBudgetCapacity(PDO $pdo, int $programId, float $additional): void
    {
        $stmt = $pdo->prepare('SELECT total_budget FROM scholarship_programs WHERE id=? FOR UPDATE');
        $stmt->execute([$programId]);
        $budget = (float) $stmt->fetchColumn();
        $committed = self::programCommittedAmount($pdo, $programId);
        if ($budget <= 0 && ($committed > 0 || $additional > 0)) throw new RuntimeException('Set a program budget before approving vouchers.');
        if ($budget > 0 && $committed + $additional > $budget) throw new RuntimeException('This request would exceed the program budget. Increase the budget or reduce batch awards first.');
    }

    private static function missingOpenBatchVoucherValue(PDO $pdo, int $programId, array $scholarIds, float $awardAmount): float
    {
        $scholarIds = array_values(array_unique(array_filter(array_map('intval', $scholarIds), static fn (int $id): bool => $id > 0)));
        if ($scholarIds === []) return 0.0;
        $placeholders = implode(',', array_fill(0, count($scholarIds), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM distribution_batches b JOIN scholars s ON s.id IN ($placeholders) AND s.status='active' WHERE b.program_id=? AND b.status IN ('draft','open') AND NOT EXISTS (SELECT 1 FROM claim_vouchers v WHERE v.batch_id=b.id AND v.scholar_id=s.id)");
        $stmt->execute(array_merge($scholarIds, [$programId]));
        return (int) $stmt->fetchColumn() * $awardAmount;
    }
}