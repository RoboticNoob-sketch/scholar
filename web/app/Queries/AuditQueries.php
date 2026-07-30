<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class AuditQueries
{
    public static function baseSql(): string
    {
        return 'SELECT a.*, u.username
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE 1=1';
    }

    public static function datatables(PDO $pdo, array $input): array
    {
        [$searchSql, $searchParams] = datatables_search_clause(
            ['u.username', 'a.action', 'a.details', 'a.entity_type', 'a.ip_address'],
            $input['search']
        );

        $base = self::baseSql() . $searchSql;
        $params = $searchParams;

        $countSql = 'SELECT COUNT(*) FROM (' . $base . ') AS counted';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $total = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

        $orderMap = [
            'a.created_at',
            'u.username',
            'a.action',
            'a.details',
            'a.ip_address',
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
