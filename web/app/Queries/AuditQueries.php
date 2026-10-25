<?php

declare(strict_types=1);

namespace App\Queries;

use PDO;

final class AuditQueries
{
    /** @return array<string, array{label: string, actions: array<string, string>}> */
    public static function actionGroups(): array
    {
        return [
            'auth' => ['label' => 'Sign-in & access', 'actions' => [
                'login' => 'Web login',
                'logout' => 'Logout',
                'login_failed' => 'Login failed',
                'mobile_login' => 'Mobile login',
                'google_login' => 'Google login',
                'google_mobile_login' => 'Google mobile login',
            ]],
            'claims' => ['label' => 'Claims & scanning', 'actions' => [
                'claim_redeemed' => 'Claim redeemed',
                'scan_failed' => 'Scan failed',
                'profile_verified' => 'Profile verified',
                'profile_verify_failed' => 'Profile verify failed',
            ]],
            'batches' => ['label' => 'Batches & vouchers', 'actions' => [
                'batch_opened' => 'Batch opened',
                'batch_closed' => 'Batch closed',
                'vouchers_generated' => 'Vouchers generated',
                'vouchers_voided' => 'Vouchers voided',
            ]],
            'applications' => ['label' => 'Applications', 'actions' => [
                'application_reviewed' => 'Application reviewed',
                'application_converted_to_scholar' => 'Converted to scholar',
                'application_document_viewed' => 'Document viewed',
            ]],
            'approvals' => ['label' => 'Approvals', 'actions' => [
                'approval_submitted' => 'Approval submitted',
                'approval_approved' => 'Approval approved',
                'approval_rejected' => 'Approval rejected',
            ]],
            'users' => ['label' => 'User management', 'actions' => [
                'user_created' => 'User created',
                'user_updated' => 'User updated',
            ]],
        ];
    }

    /** @return array<int, string> */
    public static function failureActions(): array
    {
        return ['login_failed', 'scan_failed', 'profile_verify_failed', 'approval_rejected'];
    }

    /** @return array{0: string, 1: array<int, mixed>} */
    public static function filterClause(array $filters): array
    {
        $sql = '';
        $params = [];

        $action = (string) ($filters['action'] ?? '');
        if ($action === 'failures') {
            $actions = self::failureActions();
            $sql .= ' AND a.action IN (' . implode(',', array_fill(0, count($actions), '?')) . ')';
            array_push($params, ...$actions);
        } elseif (str_starts_with($action, 'group:')) {
            $group = self::actionGroups()[substr($action, 6)] ?? null;
            if ($group) {
                $actions = array_keys($group['actions']);
                $sql .= ' AND a.action IN (' . implode(',', array_fill(0, count($actions), '?')) . ')';
                array_push($params, ...$actions);
            }
        } elseif ($action !== '') {
            $sql .= ' AND a.action = ?';
            $params[] = $action;
        }

        $user = trim((string) ($filters['user'] ?? ''));
        if (strtolower($user) === 'system') {
            $sql .= ' AND a.user_id IS NULL';
        } elseif ($user !== '') {
            $like = '%' . addcslashes($user, '%_\\') . '%';
            $sql .= ' AND (u.username LIKE ?
                OR EXISTS (SELECT 1 FROM scholars sc WHERE sc.user_id = u.id AND CONCAT(sc.first_name, " ", sc.last_name) LIKE ?)
                OR EXISTS (SELECT 1 FROM scholarship_applications ap WHERE ap.applicant_user_id = u.id AND CONCAT(ap.first_name, " ", ap.last_name) LIKE ?))';
            array_push($params, $like, $like, $like);
        }

        $from = self::validDate((string) ($filters['date_from'] ?? ''));
        if ($from !== null) {
            $sql .= ' AND a.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }

        $to = self::validDate((string) ($filters['date_to'] ?? ''));
        if ($to !== null) {
            $sql .= ' AND a.created_at < ?';
            $params[] = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00';
        }

        return [$sql, $params];
    }

    public static function datatables(PDO $pdo, array $input, array $filters = []): array
    {
        [$searchSql, $searchParams] = datatables_search_clause(
            ['u.username', 'a.action', 'a.details', 'a.entity_type', 'a.ip_address'],
            $input['search']
        );
        [$filterSql, $filterParams] = self::filterClause($filters);

        $visibility = self::visibilityClause();
        $base = self::baseSql() . $visibility . $filterSql;
        $params = array_merge($filterParams, $searchParams);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM (' . $base . $searchSql . ') AS counted');
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $total = (int) $pdo->query(
            'SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE 1=1' . $visibility
        )->fetchColumn();

        $orderMap = [
            'a.created_at',
            'u.username',
            'a.action',
            'a.details',
            'a.ip_address',
        ];
        $order = datatables_order_clause($orderMap, $input['order_column'], $input['order_dir']);

        $dataSql = $base . $searchSql . $order . ' LIMIT ' . $input['length'] . ' OFFSET ' . $input['start'];
        $stmt = $pdo->prepare($dataSql);
        $stmt->execute($params);

        return [
            'draw' => $input['draw'],
            'total' => $total,
            'filtered' => $filtered,
            'rows' => $stmt->fetchAll(),
        ];
    }

    /** SQL for a human-readable actor name: scholar name, else application name, else username. */
    public static function displayNameSql(): string
    {
        return 'COALESCE(
                    (SELECT NULLIF(TRIM(CONCAT(sc.first_name, " ", sc.last_name)), "") FROM scholars sc WHERE sc.user_id = u.id LIMIT 1),
                    (SELECT NULLIF(TRIM(CONCAT(ap.first_name, " ", ap.last_name)), "") FROM scholarship_applications ap WHERE ap.applicant_user_id = u.id ORDER BY ap.id DESC LIMIT 1),
                    u.username
                ) AS display_name';
    }

    public static function baseSql(): string
    {
        return 'SELECT a.*, u.username, ' . self::displayNameSql() . '
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE 1=1';
    }

    /** The system admin's activity is only visible to the system admin. */
    public static function visibilityClause(): string
    {
        return ' AND (u.is_system IS NULL OR u.is_system = 0 OR u.id = ' . (int) ($_SESSION['user_id'] ?? 0) . ')';
    }

    private static function validDate(string $value): ?string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
