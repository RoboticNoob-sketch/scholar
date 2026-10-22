<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class ProgramQueries
{
    public static function overview(PDO $pdo): array
    {
        $row = $pdo->query(
            'SELECT
                (SELECT COUNT(*) FROM scholarship_programs WHERE status = "active") AS active_programs,
                (SELECT COALESCE(SUM(total_budget), 0) FROM scholarship_programs) AS total_budget,
                (SELECT COALESCE(SUM(total_budget), 0) FROM scholarship_programs) -
                    (SELECT COALESCE(SUM(amount), 0) FROM claim_vouchers WHERE status IN ("pending", "claimed")) AS available_budget,
                (SELECT COUNT(*) FROM distribution_batches) AS batch_count,
                (SELECT COUNT(*) FROM distribution_batches WHERE status = "open") AS open_batch_count,
                (SELECT COUNT(*) FROM claim_vouchers WHERE status = "pending") AS pending_voucher_count,
                (SELECT COALESCE(SUM(amount), 0) FROM claim_vouchers WHERE status = "pending") AS pending_amount,
                (SELECT COUNT(*) FROM claim_vouchers WHERE status = "claimed") AS claimed_voucher_count,
                (SELECT COALESCE(SUM(amount), 0) FROM claim_vouchers WHERE status = "claimed") AS claimed_amount'
        )->fetch();

        return $row ?: [];
    }

    public static function flow(PDO $pdo, int $programId): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.program_id = p.id AND e.status = "active") AS enrolled_count,
                    (SELECT COUNT(*) FROM distribution_batches b WHERE b.program_id = p.id) AS batch_count,
                    (SELECT COUNT(*) FROM distribution_batches b WHERE b.program_id = p.id AND b.status = "open") AS open_batch_count,
                    (SELECT COUNT(*) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status = "pending") AS pending_voucher_count,
                    (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status = "pending") AS pending_amount,
                    (SELECT COUNT(*) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status = "claimed") AS claimed_voucher_count,
                    (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status = "claimed") AS claimed_amount,
                    (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status IN ("pending", "claimed")) AS committed_amount,
                    p.total_budget - (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status IN ("pending", "claimed")) AS available_budget
             FROM scholarship_programs p
             WHERE p.id = ?'
        );
        $stmt->execute([$programId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function batchFlow(PDO $pdo, int $programId): array
    {
        $stmt = $pdo->prepare(
            'SELECT b.id, b.name, b.distribution_date, b.venue, b.status,
                    COUNT(v.id) AS voucher_count,
                    SUM(CASE WHEN v.status IN ("pending", "claimed") THEN v.amount ELSE 0 END) AS committed_amount,
                    SUM(CASE WHEN v.status = "pending" THEN v.amount ELSE 0 END) AS pending_amount,
                    SUM(CASE WHEN v.status = "claimed" THEN v.amount ELSE 0 END) AS claimed_amount,
                    SUM(CASE WHEN v.status = "pending" THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN v.status = "claimed" THEN 1 ELSE 0 END) AS claimed_count
             FROM distribution_batches b
             LEFT JOIN claim_vouchers v ON v.batch_id = b.id
             WHERE b.program_id = ?
             GROUP BY b.id, b.name, b.distribution_date, b.venue, b.status
             ORDER BY b.distribution_date DESC, b.id DESC'
        );
        $stmt->execute([$programId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function baseSql(): string
    {
        return 'SELECT p.*,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.program_id = p.id AND e.status = "active") AS enrolled_count,
                    (SELECT COUNT(*) FROM distribution_batches b WHERE b.program_id = p.id) AS batch_count,
                    (SELECT COUNT(*) FROM distribution_batches b WHERE b.program_id = p.id AND b.status = "open") AS open_batch_count,
                    (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status IN ("pending", "claimed")) AS committed_amount,
                    (SELECT COUNT(*) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status = "pending") AS pending_voucher_count,
                    (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status = "pending") AS pending_amount,
                    (SELECT COUNT(*) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status = "claimed") AS claimed_voucher_count,
                    (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status = "claimed") AS claimed_amount,
                    p.total_budget - (SELECT COALESCE(SUM(v.amount), 0) FROM claim_vouchers v JOIN distribution_batches b ON b.id = v.batch_id WHERE b.program_id = p.id AND v.status IN ("pending", "claimed")) AS available_budget
                FROM scholarship_programs p
                WHERE 1=1';
    }

    public static function datatables(PDO $pdo, array $input): array
    {
        [$searchSql, $searchParams] = datatables_search_clause(
            ['p.name', 'p.academic_year', 'p.semester'],
            $input['search']
        );

        $base = self::baseSql() . $searchSql;
        $params = $searchParams;

        $countSql = 'SELECT COUNT(*) FROM (' . $base . ') AS counted';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $totalSql = 'SELECT COUNT(*) FROM scholarship_programs';
        $total = (int) $pdo->query($totalSql)->fetchColumn();

        $orderMap = [
            'p.name',
            'p.amount',
            'p.total_budget',
            'enrolled_count',
            'batch_count',
            'committed_amount',
            'claimed_amount',
            'available_budget',
            'p.status',
            'p.id',
        ];
        $order = datatables_order_clause($orderMap, $input['order_column'], $input['order_dir']);

        $dataSql = $base . $order . ' LIMIT ' . $input['length'] . ' OFFSET ' . $input['start'];
        $stmt = $pdo->prepare($dataSql);
        $stmt->execute($params);

        return [
            'draw' => $input['draw'],
            'total' => $total,
            'filtered' => $filtered,
            'rows' => $stmt->fetchAll(),
        ];
    }
}
