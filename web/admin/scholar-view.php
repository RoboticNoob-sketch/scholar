<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM scholars WHERE id = ?');
$stmt->execute([$id]);
$scholar = $stmt->fetch();
if (!$scholar) {
    flash('error', 'Scholar not found.');
    redirect('admin/scholars.php');
}

$history = $pdo->prepare(
    'SELECT v.*, b.name AS batch_name, p.name AS program_name, c.claimed_at
     FROM claim_vouchers v
     JOIN distribution_batches b ON b.id = v.batch_id
     JOIN scholarship_programs p ON p.id = b.program_id
     LEFT JOIN claims c ON c.voucher_id = v.id
     WHERE v.scholar_id = ?
     ORDER BY v.created_at DESC'
);
$history->execute([$id]);
$claims = $history->fetchAll();

$qrPayload = profile_qr_payload($scholar);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrPayload);

render_admin_layout($pdo, 'scholars', scholar_full_name($scholar), function () use ($scholar, $claims, $qrUrl, $qrPayload): void {
    echo '<div class="breadcrumb">Admin / Scholars / ' . e(scholar_full_name($scholar)) . '</div>';
    echo '<div class="page-header"><h1 class="page-title">' . e(scholar_full_name($scholar)) . '</h1>';
    echo '<div class="table-actions">';
    echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/scholar-form.php?id=' . (int) $scholar['id']) . '">EDIT SCHOLAR</a>';
    echo '</div></div>';

    echo '<div class="detail-grid">';
    echo '<div class="stack">';
    echo '<div class="card"><div class="card-title">Profile</div>';
    $photoUrl = scholar_photo_url($scholar['photo_path'] ?? null, (int) $scholar['id']);
    $photoFile = ($scholar['photo_path'] ?? '') !== ''
        ? scholar_uploads_dir() . '/' . basename((string) $scholar['photo_path'])
        : null;
    if ($photoUrl) {
        echo '<div style="margin-bottom:16px"><img class="photo-preview" src="' . e($photoUrl) . '" alt="' . e(scholar_full_name($scholar)) . '" onerror="this.style.display=\'none\'"></div>';
    }
    if ($photoFile && !is_file($photoFile)) {
        echo '<div class="alert-error" style="margin-bottom:16px"><i data-lucide="alert-circle"></i><span>Photo file missing on server. Edit scholar and upload the photo again.</span></div>';
    }
    $fields = [
        'Student No.' => $scholar['student_no'],
        'Course & Year' => trim(($scholar['course'] ?? '') . ', ' . ($scholar['year_level'] ?? ''), ', '),
        'Contact' => $scholar['email'] ?: '—',
        'Phone' => $scholar['phone'] ?: '—',
        'Status' => $scholar['status'],
    ];
    foreach ($fields as $label => $value) {
        echo '<div class="stat-row"><span>' . e($label) . '</span><span>' . e($value) . '</span></div>';
    }
    echo '</div>';

    echo '<div class="card"><div class="card-title">Voucher & Claim History</div>';
    if (!$claims) {
        echo '<div class="empty-state">No vouchers generated yet.</div>';
    }
    foreach ($claims as $c) {
        echo '<div class="activity-item"><div class="activity-top">';
        echo '<span class="activity-who">' . e($c['batch_name']) . '</span>';
        echo '<span class="badge ' . badge_class($c['status']) . '">' . e($c['status']) . '</span>';
        echo '</div><div class="activity-meta">' . e($c['program_name']) . ' · ' . e(format_money((float) $c['amount']));
        if ($c['claimed_at']) {
            echo ' · Claimed ' . e(format_datetime($c['claimed_at']));
        }
        echo '</div></div>';
    }
    echo '</div></div>';

    echo '<div class="card qr-card">';
    echo '<div class="card-title">Scholar Profile QR</div>';
    echo '<div class="qr-preview"><img src="' . e($qrUrl) . '" alt="Profile QR"></div>';
    echo '<div class="qr-caption">For identity verification at distribution</div>';
    echo '<div class="qr-payload">' . e($qrPayload) . '</div>';
    echo '</div></div>';
});
