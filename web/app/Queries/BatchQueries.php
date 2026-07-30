<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class BatchQueries
{
    public static function baseSql(): string
    {
        return 'SELECT b.*, p.name AS program_name
                FROM distribution_batches b
                JOIN scholarship_programs p ON p.id = b.program_id
                WHERE 1=1';
    }

    public static function datatables(PDO $pdo, array $input): array
    {
        [$searchSql, $searchParams] = datatables_search_clause(
            ['b.name', 'p.name', 'b.venue', 'b.status'],
            $input['search']
        );

        $base = self::baseSql() . $searchSql;
        $params = $searchParams;

        $countSql = 'SELECT COUNT(*) FROM (' . $base . ') AS counted';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $total = (int) $pdo->query('SELECT COUNT(*) FROM distribution_batches')->fetchColumn();

        $orderMap = [
            'b.name',
            'p.name',
            'b.distribution_date',
            'b.venue',
            'b.status',
            'b.id',
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

    public static function voucherDatatables(PDO $pdo, int $batchId, array $input): array
    {
        $base = 'SELECT v.id, s.student_no, s.first_name, s.last_name, v.voucher_code, v.amount, v.status, c.claimed_at
                 FROM claim_vouchers v
                 JOIN scholars s ON s.id = v.scholar_id
                 LEFT JOIN claims c ON c.voucher_id = v.id
                 WHERE v.batch_id = ?';
        $params = [$batchId];

        [$searchSql, $searchParams] = datatables_search_clause(
            ['s.student_no', 's.first_name', 's.last_name', 'v.voucher_code', 'v.status'],
            $input['search']
        );
        $base .= $searchSql;
        $params = array_merge($params, $searchParams);

        $countSql = 'SELECT COUNT(*) FROM (' . $base . ') AS counted';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM claim_vouchers WHERE batch_id = ?');
        $totalStmt->execute([$batchId]);
        $total = (int) $totalStmt->fetchColumn();

        $orderMap = [
            's.last_name',
            's.student_no',
            'v.amount',
            'v.status',
            'c.claimed_at',
            'v.id',
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
