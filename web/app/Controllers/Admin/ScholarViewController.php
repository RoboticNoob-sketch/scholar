<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use PDO;

final class ScholarViewController
{
    public static function handle(PDO $pdo): void
    {
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
$programQuery = $pdo->prepare(
    'SELECT p.name, p.amount, p.semester, p.academic_year
     FROM enrollments e JOIN scholarship_programs p ON p.id = e.program_id
     WHERE e.scholar_id = ? AND e.status = "active"
     ORDER BY p.academic_year DESC, p.semester, p.name'
);
$programQuery->execute([$id]);
$programs = $programQuery->fetchAll();

$qrPayload = profile_qr_payload($scholar);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrPayload);

$GLOBALS['page_assets'] = ['scholar_profile' => true];
render_admin_layout($pdo, 'scholars', scholar_full_name($scholar), function () use ($scholar, $claims, $programs, $qrUrl, $qrPayload): void {
    $fullName = scholar_full_name($scholar);
    $initials = strtoupper(substr((string) $scholar['first_name'], 0, 1) . substr((string) $scholar['last_name'], 0, 1));
    echo '<div class="breadcrumb">Admin / Scholars / Profile</div>';
    echo '<div class="scholar-view-heading"><div><div class="eyebrow">SCHOLAR PROFILE</div><h1 class="page-title">' . e($fullName) . '</h1><p>Student record and distribution activity</p></div>';
    echo '<div class="table-actions"><a class="btn btn-outline btn-sm" href="' . e(base_url('admin/scholars.php')) . '"><i data-lucide="arrow-left"></i> BACK TO SCHOLARS</a>';
    echo '<a class="btn btn-primary btn-sm" href="' . e(base_url('admin/scholar-form.php?id=' . (int) $scholar['id'])) . '"><i data-lucide="pencil"></i> EDIT PROFILE</a></div></div>';

    $photoUrl = scholar_photo_url($scholar['photo_path'] ?? null, (int) $scholar['id']);
    $photoFile = ($scholar['photo_path'] ?? '') !== ''
        ? scholar_uploads_dir() . '/' . basename((string) $scholar['photo_path'])
        : null;
    echo '<section class="card scholar-identity-card"><div class="scholar-identity-avatar">';
    if ($photoUrl) {
        echo '<img src="' . e($photoUrl) . '" alt="' . e($fullName) . '" width="92" height="92">';
    } else {
        echo '<span>' . e($initials !== '' ? $initials : '?') . '</span>';
    }
    echo '</div><div class="scholar-identity-main"><div class="scholar-identity-name">' . e($fullName) . '<span class="badge ' . badge_class($scholar['status']) . '">' . e($scholar['status']) . '</span></div>';
    echo '<div class="scholar-identity-number">STUDENT NO. <strong>' . e($scholar['student_no']) . '</strong></div>';
    echo '<div class="scholar-identity-facts">';
    $courseYear = trim(($scholar['course'] ?? '') . ' · ' . ($scholar['year_level'] ?? ''), ' ·');
    foreach (['Course / year' => $courseYear, 'Email' => $scholar['email'] ?: '—', 'Phone' => $scholar['phone'] ?: '—'] as $label => $value) {
        echo '<div><span>' . e($label) . '</span><strong>' . e($value) . '</strong></div>';
    }
    echo '</div></div></section>';

    if ($photoFile && !is_file($photoFile)) {
        echo '<div class="alert-error" style="margin-bottom:16px"><i data-lucide="alert-circle"></i><span>Photo file missing on server. Edit scholar and upload the photo again.</span></div>';
    }

    echo '<div class="scholar-detail-grid"><div class="stack">';
    echo '<section class="card scholar-program-card"><div class="scholar-section-heading"><div><div class="eyebrow">ELIGIBILITY</div><h2>Program assignments</h2></div><span class="scholar-section-count">' . count($programs) . '</span></div>';
    if (!$programs) {
        echo '<div class="empty-state"><i data-lucide="graduation-cap"></i><div>No program assigned yet. The SK office can request an assignment when an active program is appropriate.</div></div>';
    } else {
        foreach ($programs as $program) {
            echo '<div class="scholar-program-row"><div><strong>' . e($program['name']) . '</strong><span>' . e($program['semester']) . ' | ' . e($program['academic_year']) . '</span></div><div class="scholar-program-amount">' . e(format_money((float) $program['amount'])) . '<small>per scholar</small></div></div>';
        }
    }
    echo '</section>';

    echo '<section class="card scholar-claims-card"><div class="scholar-section-heading"><div><div class="eyebrow">DISTRIBUTION</div><h2>Voucher & claim history</h2></div><span class="scholar-section-count">' . count($claims) . '</span></div>';
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
    echo '</section></div>';

    echo '<aside class="card scholar-qr-card"><div class="eyebrow">IDENTITY CHECK</div><h2>Scholar QR</h2>';
    echo '<div class="qr-preview"><img src="' . e($qrUrl) . '" alt="Scholar identity QR"></div>';
    echo '<p class="qr-caption">Show this code to staff for identity verification.</p>';
    echo '<div class="scholar-public-id"><span>PUBLIC ID</span><strong>' . e($scholar['public_id']) . '</strong></div>';
    echo '<details class="scholar-qr-payload"><summary>View QR payload</summary><code>' . e($qrPayload) . '</code></details>';
    echo '</aside></div>';
});
unset($GLOBALS['page_assets']);

    }
}
