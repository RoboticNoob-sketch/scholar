<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class ScholarQueries
{
    public static function baseSql(): string
    {
        return 'SELECT s.*, GROUP_CONCAT(DISTINCT p.name SEPARATOR ", ") AS programs
                FROM scholars s
                LEFT JOIN enrollments e ON e.scholar_id = s.id AND e.status = "active"
                LEFT JOIN scholarship_programs p ON p.id = e.program_id
                WHERE 1=1';
    }

    /**
     * @return array{where: string, params: array<int, mixed>, group: string}
     */
    public static function filters(int $programId = 0, string $search = ''): array
    {
        $where = '';
        $params = [];

        if ($search !== '') {
            $where .= ' AND (s.student_no LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($programId > 0) {
            $where .= ' AND EXISTS (
                SELECT 1 FROM enrollments e2
                WHERE e2.scholar_id = s.id AND e2.program_id = ? AND e2.status = "active"
            )';
            $params[] = $programId;
        }

        return [
            'where' => $where,
            'params' => $params,
            'group' => ' GROUP BY s.id',
        ];
    }

    public static function datatables(PDO $pdo, array $input, int $programId = 0): array
    {
        $filters = self::filters($programId, $input['search']);
        $base = self::baseSql() . $filters['where'];
        $group = $filters['group'];
        $params = $filters['params'];

        $countSql = 'SELECT COUNT(*) FROM (' . $base . $group . ') AS counted';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $totalSql = 'SELECT COUNT(*) FROM (' . self::baseSql() . ' GROUP BY s.id) AS counted';
        $total = (int) $pdo->query($totalSql)->fetchColumn();

        $orderMap = [
            's.student_no',
            's.last_name',
            'programs',
            's.status',
            's.id',
        ];
        $order = datatables_order_clause($orderMap, $input['order_column'], $input['order_dir']);

        $dataSql = $base . $group . $order . ' LIMIT ' . $input['length'] . ' OFFSET ' . $input['start'];
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
