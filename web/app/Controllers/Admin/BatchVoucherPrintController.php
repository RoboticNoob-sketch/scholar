<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class BatchVoucherPrintController
{
    public static function handle(PDO $pdo): void
    {
        require_role($pdo, ['admin']);

        $batchId = (int) ($_GET['id'] ?? 0);
        $batchStmt = $pdo->prepare(
            'SELECT b.id, b.name, b.distribution_date, b.venue, b.status,
                    p.name AS program_name, p.amount AS program_amount
             FROM distribution_batches b
             JOIN scholarship_programs p ON p.id = b.program_id
             WHERE b.id = ?'
        );
        $batchStmt->execute([$batchId]);
        $batch = $batchStmt->fetch();

        if (!$batch) {
            http_response_code(404);
            render_head('Batch not found');
            echo '<main class="print-vouchers-page"><div class="empty-state">Batch not found.</div></main>';
            render_foot();
            return;
        }

        $voucherStmt = $pdo->prepare(
            'SELECT v.voucher_code, v.amount, s.student_no, s.first_name, s.last_name
             FROM claim_vouchers v
             JOIN scholars s ON s.id = v.scholar_id
             WHERE v.batch_id = ? AND v.status = "pending"
             ORDER BY s.last_name, s.first_name, s.student_no'
        );
        $voucherStmt->execute([$batchId]);
        $vouchers = $voucherStmt->fetchAll() ?: [];

        render_head('Print pending vouchers');
        echo '<main class="print-vouchers-page">';
        render_flash();
        echo '<header class="print-vouchers-toolbar">';
        echo '<div><div class="dashboard-eyebrow">Physical distribution</div>';
        echo '<h1 class="page-title">Print pending vouchers</h1>';
        $voucherCount = count($vouchers);
        $voucherLabel = $voucherCount === 1 ? 'voucher' : 'vouchers';
        echo '<p class="page-subtitle">' . $voucherCount . ' unclaimed ' . $voucherLabel . ' · ' . e($batch['name']) . '</p></div>';
        echo '<div class="table-actions btn-group">';
        echo '<a class="btn btn-outline btn-sm" href="' . e(base_url('admin/batch-view.php?id=' . $batchId)) . '"><i data-lucide="arrow-left"></i> Back to batch</a>';
        if ($vouchers !== []) {
            echo '<button class="btn btn-primary btn-sm" type="button" data-print-vouchers disabled><i data-lucide="printer"></i> Preparing print sheet…</button>';
        }
        echo '</div></header>';

        if ($vouchers === []) {
            echo '<div class="empty-state"><i data-lucide="ticket-check"></i>No pending vouchers to print. Claimed and void vouchers are excluded.</div>';
        } else {
            echo '<div class="print-voucher-sheet">';
            foreach ($vouchers as $voucher) {
                $fullName = trim($voucher['first_name'] . ' ' . $voucher['last_name']);
                $payload = 'VCH|' . $voucher['voucher_code'];
                echo '<article class="print-voucher">';
                echo '<header class="print-voucher-head">';
                echo '<img src="' . e(school_logo_url()) . '" alt="" class="print-voucher-logo">';
                echo '<div class="print-voucher-brand"><strong>Scholarly</strong><span>Scholarship assistance</span></div>';
                echo '<span class="print-voucher-type">OFFICE CLAIM</span>';
                echo '</header>';
                echo '<div class="print-voucher-body">';
                echo '<div class="print-voucher-qr" data-voucher-qr="' . e($payload) . '"></div>';
                echo '<div class="print-voucher-details">';
                echo '<div class="print-voucher-eyebrow">Physical claim voucher</div>';
                echo '<h2>' . e($fullName) . '</h2>';
                echo '<div class="print-voucher-student">Student No. ' . e($voucher['student_no']) . '</div>';
                echo '<div class="print-voucher-amount">' . e(format_money((float) $voucher['amount'])) . '</div>';
                echo '<div class="print-voucher-code-label">Voucher code</div>';
                echo '<code class="print-voucher-code">' . e($voucher['voucher_code']) . '</code>';
                echo '</div></div>';
                echo '<div class="print-voucher-batch">';
                echo '<div><span>Program</span><strong>' . e($batch['program_name']) . '</strong></div>';
                echo '<div><span>Distribution</span><strong>' . e(format_date($batch['distribution_date'])) . ' · ' . e($batch['venue']) . '</strong></div>';
                echo '</div>';
                echo '<footer class="print-voucher-foot"><strong>NO PHONE REQUIRED</strong><span>Present this voucher and your school ID at the office while distribution is open. One-time claim; keep this code private.</span></footer>';
                echo '<section class="print-voucher-stub" aria-label="Office claim record">';
                echo '<div class="print-voucher-tear"><i data-lucide="scissors"></i><span>DETACH AFTER CLAIM</span></div>';
                echo '<div class="print-voucher-stub-content">';
                echo '<div class="print-stub-heading"><span>OFFICE CLAIM RECORD</span><strong>CLAIMED</strong><span class="print-stub-check" aria-hidden="true"></span></div>';
                echo '<div class="print-stub-identity"><strong>' . e($fullName) . '</strong><code>' . e($voucher['voucher_code']) . '</code></div>';
                echo '<div class="print-stub-fields"><span>Staff initials __________________</span><span>Claim date / time _______________</span></div>';
                echo '</div>';
                echo '<p class="print-stub-note">Complete only after successful QR scan. Retain this stub at the office.</p>';
                echo '</section>';
                echo '</article>';
            }
            echo '</div>';
            echo '<p class="print-vouchers-note">Print on A4 at actual size and give each intact ticket to its named student. After a successful scan, complete and detach the office stub.</p>';
        }

        echo '</main>';
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>';
        echo '<script src="' . e(asset_url('assets/js/print-vouchers.js')) . '"></script>';
        render_foot();
    }
}