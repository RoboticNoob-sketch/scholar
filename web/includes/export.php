<?php

declare(strict_types=1);

function csv_download_headers(string $filename): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

/**
 * Stream a CSV file with Scholarly template rows (metadata, blank line, headers, data).
 *
 * @param iterable<int, array<int, string>> $rows
 */
function stream_csv_template(string $filename, array $metaLines, array $columns, iterable $rows, ?array $footerRow = null): never
{
    csv_download_headers($filename);

    $out = fopen('php://output', 'w');
    if ($out === false) {
        throw new RuntimeException('Could not open CSV output stream.');
    }

    fwrite($out, "\xEF\xBB\xBF");

    foreach ($metaLines as $line) {
        fputcsv($out, [(string) $line]);
    }
    fputcsv($out, []);
    fputcsv($out, array_column($columns, 'label'));

    foreach ($rows as $row) {
        fputcsv($out, $row);
    }

    if ($footerRow !== null) {
        fputcsv($out, []);
        fputcsv($out, $footerRow);
    }

    fclose($out);
    exit;
}

function stream_csv_from_query(PDO $pdo, string $filename, array $metaLines, array $columns, string $sql, array $params, ?array $footerRow = null): never
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    stream_csv_template($filename, $metaLines, $columns, export_rows_from_statement($stmt, $columns), $footerRow);
}

function export_filename(string $slug): string
{
    return $slug . '-' . date('Y-m-d') . '.csv';
}
