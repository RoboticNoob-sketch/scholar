<?php

declare(strict_types=1);

function claims_report_filters(array $src): array
{
    $period = (string) ($src['period'] ?? '');
    $from = (string) ($src['date_from'] ?? '');
    $to = (string) ($src['date_to'] ?? '');
    $valid = static fn(string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

    $today = date('Y-m-d');
    switch ($period) {
        case 'today':
            $from = $to = $today;
            break;
        case '7d':
            $from = date('Y-m-d', strtotime('-6 days'));
            $to = $today;
            break;
        case '30d':
            $from = date('Y-m-d', strtotime('-29 days'));
            $to = $today;
            break;
        case 'month':
            $from = date('Y-m-01');
            $to = $today;
            break;
        default:
            $period = $valid($from) || $valid($to) ? 'custom' : '';
    }
    if (!$valid($from)) {
        $from = '';
    }
    if (!$valid($to)) {
        $to = '';
    }

    return [
        'period' => $period,
        'date_from' => $from,
        'date_to' => $to,
        'program_id' => max(0, (int) ($src['program_id'] ?? 0)),
        'batch_id' => max(0, (int) ($src['batch_id'] ?? 0)),
        'staff_id' => max(0, (int) ($src['staff_id'] ?? 0)),
    ];
}

function claims_report_where(array $filters): array
{
    $sql = '';
    $params = [];
    if ($filters['batch_id'] > 0) {
        $sql .= ' AND b.id = ?';
        $params[] = $filters['batch_id'];
    }
    if ($filters['program_id'] > 0) {
        $sql .= ' AND b.program_id = ?';
        $params[] = $filters['program_id'];
    }
    if ($filters['staff_id'] > 0) {
        $sql .= ' AND c.staff_user_id = ?';
        $params[] = $filters['staff_id'];
    }
    if ($filters['date_from'] !== '') {
        $sql .= ' AND c.claimed_at >= ?';
        $params[] = $filters['date_from'] . ' 00:00:00';
    }
    if ($filters['date_to'] !== '') {
        $sql .= ' AND c.claimed_at <= ?';
        $params[] = $filters['date_to'] . ' 23:59:59';
    }

    return [$sql, $params];
}

function claims_report_sql(array $filters): array
{
    $query = claims_report_base_sql($filters);
    $query['sql'] .= ' ORDER BY c.claimed_at DESC';

    return $query;
}

function claims_report_filter_labels(PDO $pdo, array $filters): array
{
    $lines = [];
    $lookup = static function (string $table, int $id) use ($pdo): string {
        $col = $table === 'users' ? 'username' : 'name';
        $stmt = $pdo->prepare("SELECT $col FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        return (string) ($stmt->fetchColumn() ?: '#' . $id);
    };
    if ($filters['program_id'] > 0) {
        $lines[] = 'Program: ' . $lookup('scholarship_programs', $filters['program_id']);
    }
    if ($filters['batch_id'] > 0) {
        $lines[] = 'Batch: ' . $lookup('distribution_batches', $filters['batch_id']);
    }
    if ($filters['staff_id'] > 0) {
        $lines[] = 'Processed by: ' . $lookup('users', $filters['staff_id']);
    }
    if ($filters['date_from'] !== '' || $filters['date_to'] !== '') {
        $lines[] = 'Period: ' . ($filters['date_from'] ?: 'start') . ' to ' . ($filters['date_to'] ?: 'today');
    }

    return $lines ?: ['Filter: All claims'];
}

function claims_report_base_sql(array $filters): array
{
    $sql = 'SELECT c.claimed_at, v.amount, v.voucher_code, v.status, b.name AS batch_name, p.name AS program_name,
                   s.student_no, s.first_name, s.last_name, u.username AS staff_name
            FROM claims c
            JOIN claim_vouchers v ON v.id = c.voucher_id
            JOIN distribution_batches b ON b.id = v.batch_id
            JOIN scholarship_programs p ON p.id = b.program_id
            JOIN scholars s ON s.id = v.scholar_id
            JOIN users u ON u.id = c.staff_user_id
            WHERE 1=1';
    [$where, $params] = claims_report_where($filters);

    return ['sql' => $sql . $where, 'params' => $params];
}

function claims_report_summary(PDO $pdo, array $filters): array
{
    [$where, $params] = claims_report_where($filters);
    $sql = 'SELECT COUNT(*) AS total_claims, COALESCE(SUM(v.amount), 0) AS total_amount,
                   COUNT(DISTINCT v.scholar_id) AS scholars
            FROM claims c
            JOIN claim_vouchers v ON v.id = c.voucher_id
            JOIN distribution_batches b ON b.id = v.batch_id
            WHERE 1=1' . $where;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch() ?: [];
    $claims = (int) ($row['total_claims'] ?? 0);
    $amount = (float) ($row['total_amount'] ?? 0);

    // Claim rate: vouchers claimed vs. all vouchers (excluding void) in the filtered scope
    $vSql = 'SELECT COUNT(*) AS issued, SUM(v.status = \'claimed\') AS claimed, SUM(v.status = \'pending\') AS pending,
                    SUM(v.status = \'expired\') AS expired
             FROM claim_vouchers v
             JOIN distribution_batches b ON b.id = v.batch_id
             WHERE v.status <> \'void\'';
    $vParams = [];
    if ($filters['batch_id'] > 0) {
        $vSql .= ' AND b.id = ?';
        $vParams[] = $filters['batch_id'];
    }
    if ($filters['program_id'] > 0) {
        $vSql .= ' AND b.program_id = ?';
        $vParams[] = $filters['program_id'];
    }
    $vStmt = $pdo->prepare($vSql);
    $vStmt->execute($vParams);
    $v = $vStmt->fetch() ?: [];
    $issued = (int) ($v['issued'] ?? 0);
    $claimedAll = (int) ($v['claimed'] ?? 0);

    return [
        'total_claims' => $claims,
        'total_amount' => $amount,
        'scholars' => (int) ($row['scholars'] ?? 0),
        'average' => $claims > 0 ? $amount / $claims : 0.0,
        'issued' => $issued,
        'pending' => (int) ($v['pending'] ?? 0),
        'expired' => (int) ($v['expired'] ?? 0),
        'claim_rate' => $issued > 0 ? (int) round($claimedAll / $issued * 100) : 0,
    ];
}

function claims_report_trend(PDO $pdo, array $filters): array
{
    [$where, $params] = claims_report_where($filters);
    $stmt = $pdo->prepare(
        'SELECT DATE(c.claimed_at) AS d, COUNT(*) AS n, SUM(v.amount) AS amt
         FROM claims c
         JOIN claim_vouchers v ON v.id = c.voucher_id
         JOIN distribution_batches b ON b.id = v.batch_id
         WHERE 1=1' . $where . ' GROUP BY DATE(c.claimed_at) ORDER BY d DESC LIMIT 30'
    );
    $stmt->execute($params);

    return array_reverse($stmt->fetchAll());
}

function claims_report_breakdown(PDO $pdo, array $filters): array
{
    [$where, $params] = claims_report_where($filters);
    $stmt = $pdo->prepare(
        'SELECT p.name AS program_name, COUNT(*) AS n, SUM(v.amount) AS amt
         FROM claims c
         JOIN claim_vouchers v ON v.id = c.voucher_id
         JOIN distribution_batches b ON b.id = v.batch_id
         JOIN scholarship_programs p ON p.id = b.program_id
         WHERE 1=1' . $where . ' GROUP BY p.id, p.name ORDER BY amt DESC LIMIT 8'
    );
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function batch_vouchers_sql(int $batchId): array
{
    return [
        'sql' => 'SELECT v.id, s.student_no, s.first_name, s.last_name, v.voucher_code, v.amount, v.status, c.claimed_at,
                         b.name AS batch_name, p.name AS program_name
                  FROM claim_vouchers v
                  JOIN scholars s ON s.id = v.scholar_id
                  JOIN distribution_batches b ON b.id = v.batch_id
                  JOIN scholarship_programs p ON p.id = b.program_id
                  LEFT JOIN claims c ON c.voucher_id = v.id
                  WHERE v.batch_id = ?
                  ORDER BY s.last_name, s.first_name',
        'params' => [$batchId],
    ];
}

function claims_report_columns(): array
{
    return [
        ['key' => 'claimed_at', 'label' => 'Claimed At', 'format' => 'datetime'],
        ['key' => 'student_no', 'label' => 'Student No.'],
        ['key' => 'scholar_name', 'label' => 'Scholar Name'],
        ['key' => 'program_name', 'label' => 'Program'],
        ['key' => 'batch_name', 'label' => 'Batch'],
        ['key' => 'amount', 'label' => 'Amount (PHP)', 'format' => 'money'],
        ['key' => 'staff_name', 'label' => 'Processed By'],
    ];
}

function batch_vouchers_columns(): array
{
    return [
        ['key' => 'student_no', 'label' => 'Student No.'],
        ['key' => 'scholar_name', 'label' => 'Scholar Name'],
        ['key' => 'voucher_code', 'label' => 'Voucher Code'],
        ['key' => 'amount', 'label' => 'Amount (PHP)', 'format' => 'money'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'claimed_at', 'label' => 'Claimed At', 'format' => 'datetime'],
    ];
}

function export_row_value(array $row, array $column): string
{
    $key = $column['key'];
    if ($key === 'scholar_name') {
        return scholar_full_name($row);
    }

    $value = $row[$key] ?? '';
    return match ($column['format'] ?? 'text') {
        'datetime' => $value !== '' && $value !== null ? format_datetime((string) $value) : '',
        'date' => $value !== '' && $value !== null ? format_date((string) $value) : '',
        'money' => format_money((float) $value),
        default => (string) $value,
    };
}

function export_rows_from_statement(PDOStatement $stmt, array $columns): Generator
{
    while ($row = $stmt->fetch()) {
        $line = [];
        foreach ($columns as $column) {
            $line[] = export_row_value($row, $column);
        }
        yield $line;
    }
}

function report_meta_lines(string $title, array $extra = []): array
{
    $config = app_config();
    $lines = [
        $config['school_name'] ?? 'Scholarly',
        $config['app_name'] ?? 'Scholarly',
        $title,
        'Generated: ' . format_datetime(date('Y-m-d H:i:s')),
    ];
    foreach ($extra as $line) {
        if ($line !== '') {
            $lines[] = $line;
        }
    }
    return $lines;
}
