<?php

declare(strict_types=1);

function pagination_page(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

function pagination_per_page(): int
{
    $perPage = (int) ($_GET['per_page'] ?? 25);
    return max(10, min(100, $perPage));
}

/**
 * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int, offset: int}
 */
function paginate(PDO $pdo, string $sql, array $params, int $page, int $perPage = 25): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') AS _paginate_count';
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $dataSql = $sql . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $rows = $dataStmt->fetchAll();

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function pagination_query(array $preserve = []): array
{
    $query = [];
    foreach ($preserve as $key) {
        if (!isset($_GET[$key]) || $_GET[$key] === '') {
            continue;
        }
        $query[$key] = (string) $_GET[$key];
    }
    return $query;
}

function pagination_url(string $path, array $query, int $page): string
{
    $query['page'] = (string) $page;
    $qs = http_build_query($query);
    return base_url($path . ($qs !== '' ? '?' . $qs : ''));
}

function render_pagination(string $path, array $result, array $preserveQuery = []): void
{
    if ($result['total_pages'] <= 1) {
        return;
    }

    $query = pagination_query($preserveQuery);
    $page = $result['page'];
    $totalPages = $result['total_pages'];

    echo '<nav class="pagination" aria-label="Table pages">';
    echo '<a class="pagination-link' . ($page <= 1 ? ' disabled' : '') . '" href="'
        . e($page <= 1 ? '#' : pagination_url($path, $query, $page - 1)) . '">Prev</a>';

    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) {
        echo '<a class="pagination-link" href="' . e(pagination_url($path, $query, 1)) . '">1</a>';
        if ($start > 2) {
            echo '<span class="pagination-ellipsis">…</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page ? ' active' : '';
        echo '<a class="pagination-link' . $active . '" href="' . e(pagination_url($path, $query, $i)) . '">' . $i . '</a>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            echo '<span class="pagination-ellipsis">…</span>';
        }
        echo '<a class="pagination-link" href="' . e(pagination_url($path, $query, $totalPages)) . '">' . $totalPages . '</a>';
    }

    echo '<a class="pagination-link' . ($page >= $totalPages ? ' disabled' : '') . '" href="'
        . e($page >= $totalPages ? '#' : pagination_url($path, $query, $page + 1)) . '">Next</a>';
    echo '</nav>';
}

function render_table_footer(string $path, array $result, string $itemLabel, array $preserveQuery = []): void
{
    $count = count($result['rows']);
    $from = $result['total'] === 0 ? 0 : $result['offset'] + 1;
    $to = $result['total'] === 0 ? 0 : min($result['offset'] + $count, $result['total']);

    echo '<div class="table-footer">';
    echo '<div class="table-meta">Showing ' . $from . '–' . $to . ' of ' . $result['total'] . ' ' . e($itemLabel) . '</div>';
    render_pagination($path, $result, $preserveQuery);
    echo '</div>';
}
