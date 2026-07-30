<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class ReportQueries
{
    public static function datatables(PDO $pdo, array $input, int $batchId = 0): array
    {
        $query = claims_report_sql($batchId);
        $sql = preg_replace('/\s+ORDER BY .+$/i', '', $query['sql']);
        $params = $query['params'];

        if ($input['search'] !== '') {
            [$searchSql, $searchParams] = datatables_search_clause(
                [
                    's.student_no',
                    's.first_name',
                    's.last_name',
                    'p.name',
                    'b.name',
                    'u.username',
                    'v.voucher_code',
                ],
                $input['search']
            );
            $sql .= $searchSql;
            $params = array_merge($params, $searchParams);
        }

        $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') AS counted';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $summary = claims_report_summary($pdo, $batchId);
        $total = $summary['total_claims'];

        $orderMap = [
            'c.claimed_at',
            's.last_name',
            'p.name',
            'b.name',
            'v.amount',
            'u.username',
        ];
        $order = datatables_order_clause($orderMap, $input['order_column'], $input['order_dir']);

        $dataSql = $sql . $order . ' LIMIT ' . $input['length'] . ' OFFSET ' . $input['start'];
        $stmt = $pdo->prepare($dataSql);
        $stmt->execute($params);

        return [
            'draw' => $input['draw'],
            'total' => $total,
            'filtered' => $filtered,
            'rows' => $stmt->fetchAll(),
            'summary' => $summary,
        ];
    }
}
