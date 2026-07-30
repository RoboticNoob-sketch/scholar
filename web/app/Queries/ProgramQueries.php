<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class ProgramQueries
{
    public static function baseSql(): string
    {
        return 'SELECT p.*, COUNT(DISTINCT e.scholar_id) AS enrolled_count
                FROM scholarship_programs p
                LEFT JOIN enrollments e ON e.program_id = p.id AND e.status = "active"
                WHERE 1=1';
    }

    public static function datatables(PDO $pdo, array $input): array
    {
        [$searchSql, $searchParams] = datatables_search_clause(
            ['p.name', 'p.academic_year', 'p.semester'],
            $input['search']
        );

        $base = self::baseSql() . $searchSql . ' GROUP BY p.id';
        $params = $searchParams;

        $countSql = 'SELECT COUNT(*) FROM (' . $base . ') AS counted';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $totalSql = 'SELECT COUNT(*) FROM (' . self::baseSql() . ' GROUP BY p.id) AS counted';
        $total = (int) $pdo->query($totalSql)->fetchColumn();

        $orderMap = [
            'p.name',
            'p.amount',
            'p.academic_year',
            'p.semester',
            'enrolled_count',
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
