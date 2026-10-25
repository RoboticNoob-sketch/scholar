<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class DashboardQueries
{
    public static function stats(PDO $pdo): array
    {
        $row = $pdo->query(
            'SELECT
                (SELECT COUNT(*) FROM scholars WHERE status = "active") AS active_scholars,
                (SELECT COUNT(*) FROM scholars) AS total_scholars,
                (SELECT COUNT(*) FROM scholarship_programs WHERE status = "active") AS active_programs,
                (SELECT COUNT(*) FROM distribution_batches WHERE status = "open") AS open_batches,
                (SELECT COUNT(*) FROM claims WHERE DATE(claimed_at) = CURDATE()) AS claims_today,
                (SELECT COUNT(*) FROM claim_vouchers WHERE status = "pending") AS pending_vouchers,
                (SELECT COALESCE(SUM(v.amount), 0)
                 FROM claims c
                 JOIN claim_vouchers v ON v.id = c.voucher_id
                 WHERE YEAR(c.claimed_at) = YEAR(CURDATE())
                   AND MONTH(c.claimed_at) = MONTH(CURDATE())) AS disbursed_month'
        )->fetch();

        return [
            'active_scholars' => (int) ($row['active_scholars'] ?? 0),
            'total_scholars' => (int) ($row['total_scholars'] ?? 0),
            'active_programs' => (int) ($row['active_programs'] ?? 0),
            'open_batches' => (int) ($row['open_batches'] ?? 0),
            'claims_today' => (int) ($row['claims_today'] ?? 0),
            'pending_vouchers' => (int) ($row['pending_vouchers'] ?? 0),
            'disbursed_month' => (float) ($row['disbursed_month'] ?? 0),
        ];
    }

    public static function openBatchProgress(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT b.id, b.name, b.distribution_date, p.name AS program_name,
                    SUM(CASE WHEN v.status = "claimed" THEN 1 ELSE 0 END) AS claimed,
                    SUM(CASE WHEN v.status = "pending" THEN 1 ELSE 0 END) AS pending,
                    COUNT(v.id) AS total
             FROM distribution_batches b
             JOIN scholarship_programs p ON p.id = b.program_id
             LEFT JOIN claim_vouchers v ON v.batch_id = b.id
             WHERE b.status = "open"
             GROUP BY b.id, b.name, b.distribution_date, p.name
             ORDER BY b.distribution_date DESC'
        );

        return $stmt->fetchAll() ?: [];
    }

    public static function recentClaims(PDO $pdo, int $limit = 6): array
    {
        $stmt = $pdo->prepare(
            'SELECT c.claimed_at, v.amount, s.student_no, s.first_name, s.last_name,
                    b.name AS batch_name, u.username AS staff_name
             FROM claims c
             JOIN claim_vouchers v ON v.id = c.voucher_id
             JOIN scholars s ON s.id = v.scholar_id
             JOIN distribution_batches b ON b.id = v.batch_id
             JOIN users u ON u.id = c.staff_user_id
             ORDER BY c.claimed_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public static function attention(PDO $pdo): array
    {
        $row = $pdo->query(
            'SELECT
                (SELECT COUNT(*) FROM scholarship_applications WHERE status IN ("submitted","under_review","needs_info")) AS applications,
                (SELECT COUNT(*) FROM approval_requests WHERE status = "pending") AS approvals,
                (SELECT COUNT(*) FROM audit_logs WHERE action IN ("login_failed","scan_failed") AND created_at >= NOW() - INTERVAL 1 DAY) AS failures'
        )->fetch();

        return [
            'applications' => (int) ($row['applications'] ?? 0),
            'approvals' => (int) ($row['approvals'] ?? 0),
            'failures' => (int) ($row['failures'] ?? 0),
        ];
    }

    /** Claims and amount per day for the last $days days, zero-filled. */
    public static function claimsTrend(PDO $pdo, int $days = 14): array
    {
        $stmt = $pdo->prepare(
            'SELECT DATE(c.claimed_at) AS d, COUNT(*) AS n, COALESCE(SUM(v.amount), 0) AS amount
             FROM claims c
             JOIN claim_vouchers v ON v.id = c.voucher_id
             WHERE c.claimed_at >= CURDATE() - INTERVAL ? DAY
             GROUP BY DATE(c.claimed_at)'
        );
        $stmt->bindValue(1, $days - 1, PDO::PARAM_INT);
        $stmt->execute();
        $found = [];
        foreach ($stmt->fetchAll() ?: [] as $r) {
            $found[$r['d']] = $r;
        }

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $out[] = [
                'date' => $d,
                'count' => (int) ($found[$d]['n'] ?? 0),
                'amount' => (float) ($found[$d]['amount'] ?? 0),
            ];
        }

        return $out;
    }

    public static function recentActivity(PDO $pdo, int $limit = 8): array
    {
        $stmt = $pdo->prepare(
            'SELECT a.*, u.username, ' . AuditQueries::displayNameSql() . '
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE (u.is_system IS NULL OR u.is_system = 0 OR u.id = ' . (int) ($_SESSION['user_id'] ?? 0) . ')
             ORDER BY a.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }
}
