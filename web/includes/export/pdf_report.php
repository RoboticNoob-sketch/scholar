<?php

declare(strict_types=1);

/**
 * Minimal PDF generator for tabular admin reports (no external dependencies).
 */
final class SimplePdf
{
    /** @var list<string> */
    private array $pages = [];

    private int $pageIndex = -1;

    private float $cursorY = 0;

    private float $margin = 36;

    private float $width = 842;

    private float $height = 595;

    public function __construct(private readonly bool $landscape = true)
    {
        if (!$landscape) {
            $this->width = 595;
            $this->height = 842;
        }
        $this->addPage();
    }

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->pageIndex++;
        $this->cursorY = $this->height - $this->margin;
    }

    public function text(float $x, float $y, string $text, int $size = 10, bool $bold = false): void
    {
        $font = $bold ? '/F2' : '/F1';
        $escaped = $this->escape($text);
        $this->pages[$this->pageIndex] .= sprintf(
            "BT %s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
            $font,
            $size,
            $x,
            $y,
            $escaped
        );
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->pages[$this->pageIndex] .= sprintf(
            "%.2F %.2F m %.2F %.2F l S\n",
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    public function rect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        [$r, $g, $b] = $rgb;
        $this->pages[$this->pageIndex] .= sprintf(
            "%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n",
            $r,
            $g,
            $b,
            $x,
            $y,
            $w,
            $h
        );
    }

    public function cursorY(): float
    {
        return $this->cursorY;
    }

    public function setCursorY(float $y): void
    {
        $this->cursorY = $y;
    }

    public function contentWidth(): float
    {
        return $this->width - ($this->margin * 2);
    }

    public function margin(): float
    {
        return $this->margin;
    }

    public function pageBottom(): float
    {
        return $this->margin + 28;
    }

    public function output(): string
    {
        $pageCount = count($this->pages);
        $fontRegularId = 3 + ($pageCount * 2);
        $fontBoldId = $fontRegularId + 1;

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $pageObjectIds = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjectIds[] = 3 + ($i * 2);
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(
            static fn (int $id): string => $id . ' 0 R',
            $pageObjectIds
        )) . '] /Count ' . $pageCount . ' >>';

        foreach ($this->pages as $index => $content) {
            $pageId = 3 + ($index * 2);
            $contentId = $pageId + 1;
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Contents %d 0 R /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> >>',
                $this->width,
                $this->height,
                $contentId,
                $fontRegularId,
                $fontBoldId
            );
            $stream = "q 1 0 0 1 0 0 cm\n" . $content . "Q";
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objects[$fontRegularId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[$fontBoldId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? $xrefPos);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }

    private function escape(string $text): string
    {
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '?', $text) ?? '';
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}

function pdf_sanitize(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    return $text !== false ? $text : preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
}

function pdf_download_headers(string $filename): void
{
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

/**
 * @param list<string> $metaLines
 * @param list<array{label: string, width: float}> $columns
 * @param list<array<int, string>> $rows
 */
function stream_pdf_table_report(string $filename, string $title, array $metaLines, array $columns, array $rows, ?string $footer = null): never
{
    $pdf = new SimplePdf(true);
    $x = $pdf->margin();
    $width = $pdf->contentWidth();
    $y = $pdf->cursorY();

    $pdf->text($x, $y, pdf_sanitize(app_config()['app_name'] ?? 'Scholarly'), 16, true);
    $y -= 18;
    $pdf->text($x, $y, pdf_sanitize($title), 12, true);
    $y -= 16;

    foreach ($metaLines as $line) {
        $pdf->text($x, $y, pdf_sanitize($line), 9);
        $y -= 12;
    }
    $y -= 6;

    $tableWidth = $width;
    $colWidths = array_column($columns, 'width');
    $scale = $tableWidth / array_sum($colWidths);
    $colWidths = array_map(static fn (float $w): float => $w * $scale, $colWidths);

    $headerHeight = 18;
    $rowHeight = 16;
    $pdf->rect($x, $y - $headerHeight + 4, $tableWidth, $headerHeight, [0.12, 0.16, 0.14]);
    $colX = $x;
    foreach ($columns as $index => $column) {
        $pdf->text($colX + 4, $y - 10, pdf_sanitize($column['label']), 8, true);
        $colX += $colWidths[$index];
    }
    $y -= $headerHeight;

    foreach ($rows as $rowIndex => $row) {
        if ($y - $rowHeight < $pdf->pageBottom()) {
            $pdf->addPage();
            $y = $pdf->cursorY();
        }

        if ($rowIndex % 2 === 0) {
            $pdf->rect($x, $y - $rowHeight + 4, $tableWidth, $rowHeight, [0.95, 0.96, 0.95]);
        }

        $colX = $x;
        foreach ($columns as $colIndex => $column) {
            $cell = pdf_sanitize((string) ($row[$colIndex] ?? ''));
            if (strlen($cell) > 42) {
                $cell = substr($cell, 0, 39) . '...';
            }
            $pdf->text($colX + 4, $y - 10, $cell, 7);
            $colX += $colWidths[$colIndex];
        }
        $y -= $rowHeight;
    }

    if ($footer !== null) {
        $y -= 8;
        $pdf->text($x, $y, pdf_sanitize($footer), 9, true);
    }

    pdf_download_headers($filename);
    echo $pdf->output();
    exit;
}
