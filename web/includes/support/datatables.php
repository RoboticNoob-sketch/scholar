<?php

declare(strict_types=1);

function datatables_input(): array
{
    $source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

    return [
        'draw' => (int) ($source['draw'] ?? 1),
        'start' => max(0, (int) ($source['start'] ?? 0)),
        'length' => max(1, min(100, (int) ($source['length'] ?? 25))),
        'search' => trim((string) ($source['search']['value'] ?? $source['search'] ?? '')),
        'order_column' => (int) ($source['order'][0]['column'] ?? 0),
        'order_dir' => strtolower((string) ($source['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC',
    ];
}

function datatables_json(int $draw, int $total, int $filtered, array $data): never
{
    header('Content-Type: application/json; charset=utf-8');
    $flags = JSON_THROW_ON_ERROR;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data,
    ], $flags);
    exit;
}

function datatables_error(int $draw, string $message, int $status = 500): never
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    $flags = JSON_THROW_ON_ERROR;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $message,
    ], $flags);
    exit;
}

/**
 * @param list<string> $searchColumns SQL expressions to OR together with LIKE
 * @return array{0: string, 1: array<int, string>}
 */
function datatables_search_clause(array $searchColumns, string $term): array
{
    if ($term === '' || $searchColumns === []) {
        return ['', []];
    }

    $like = '%' . $term . '%';
    $parts = [];
    $params = [];
    foreach ($searchColumns as $column) {
        $parts[] = $column . ' LIKE ?';
        $params[] = $like;
    }

    return [' AND (' . implode(' OR ', $parts) . ')', $params];
}

function datatables_order_clause(array $columnMap, int $columnIndex, string $direction): string
{
    $columns = array_values($columnMap);
    $column = $columns[$columnIndex] ?? $columns[0] ?? '1';
    return ' ORDER BY ' . $column . ' ' . ($direction === 'DESC' ? 'DESC' : 'ASC');
}

function require_admin_datatables(PDO $pdo): array
{
    $input = datatables_input();
    $user = current_user($pdo);
    if (!$user) {
        datatables_error($input['draw'], 'Session expired. Please sign in again.', 401);
    }
    if (!in_array($user['role'], ['admin'], true)) {
        datatables_error($input['draw'], 'Forbidden', 403);
    }
    return $input;
}
