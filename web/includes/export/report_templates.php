<?php

declare(strict_types=1);

function claims_report_sql(int $batchId = 0): array
{
    $query = claims_report_base_sql($batchId);
    $query['sql'] .= ' ORDER BY c.claimed_at DESC';

    return $query;
}

function claims_report_base_sql(int $batchId = 0): array
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
    $params = [];
    if ($batchId > 0) {
        $sql .= ' AND b.id = ?';
        $params[] = $batchId;
    }

    return ['sql' => $sql, 'params' => $params];
}

function claims_report_summary(PDO $pdo, int $batchId = 0): array
{
    $base = claims_report_sql($batchId);
    $sql = 'SELECT COUNT(*) AS total_claims, COALESCE(SUM(v.amount), 0) AS total_amount
            FROM claims c
            JOIN claim_vouchers v ON v.id = c.voucher_id
            JOIN distribution_batches b ON b.id = v.batch_id
            WHERE 1=1';
    $params = [];
    if ($batchId > 0) {
        $sql .= ' AND b.id = ?';
        $params[] = $batchId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch() ?: ['total_claims' => 0, 'total_amount' => 0];

    return [
        'total_claims' => (int) $row['total_claims'],
        'total_amount' => (float) $row['total_amount'],
    ];
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
