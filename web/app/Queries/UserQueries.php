<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class UserQueries
{
    public static function baseSql(): string
    {
        return 'SELECT u.*, s.id AS scholar_id, s.student_no, s.first_name, s.last_name
                FROM users u
                LEFT JOIN scholars s ON s.user_id = u.id
                WHERE 1=1';
    }

    /**
     * @return array{where: string, params: array<int, mixed>}
     */
    public static function filters(string $role = '', string $status = '', string $search = ''): array
    {
        $where = '';
        $params = [];

        if ($search !== '') {
            $where .= ' AND u.username LIKE ?';
            $params[] = '%' . $search . '%';
        }
        if (in_array($role, ['admin', 'staff', 'student'], true)) {
            $where .= ' AND u.role = ?';
            $params[] = $role;
        }
        if (in_array($status, ['active', 'inactive'], true)) {
            $where .= ' AND u.status = ?';
            $params[] = $status;
        }

        return ['where' => $where, 'params' => $params];
    }

    public static function stats(PDO $pdo): array
    {
        $counts = $pdo->query(
            'SELECT role, status, COUNT(*) AS cnt FROM users GROUP BY role, status'
        )->fetchAll();

        $stats = ['total' => 0, 'active' => 0, 'admin' => 0, 'staff' => 0, 'student' => 0];
        foreach ($counts as $row) {
            $stats['total'] += (int) $row['cnt'];
            if ($row['status'] === 'active') {
                $stats['active'] += (int) $row['cnt'];
                $stats[$row['role']] = ($stats[$row['role']] ?? 0) + (int) $row['cnt'];
            }
        }

        return $stats;
    }

    public static function datatables(PDO $pdo, array $input, string $role = '', string $status = ''): array
    {
        $filters = self::filters($role, $status, $input['search']);
        $base = self::baseSql() . $filters['where'];
        $params = $filters['params'];

        $countSql = 'SELECT COUNT(*) FROM (' . $base . ') AS counted';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $total = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        $orderMap = [
            'u.username',
            'u.role',
            'u.status',
            's.student_no',
            'u.created_at',
            'u.id',
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
