<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role($pdo, ['staff']);

$batches = $pdo->query(
    'SELECT id, name FROM distribution_batches WHERE status = "open" ORDER BY distribution_date DESC'
)->fetchAll();

$claimsToday = $pdo->prepare('SELECT COUNT(*) FROM claims WHERE staff_user_id = ? AND DATE(claimed_at) = CURDATE()');
$claimsToday->execute([$user['id']]);
$todayCount = (int) $claimsToday->fetchColumn();

$lastClaim = $pdo->prepare('SELECT claimed_at FROM claims WHERE staff_user_id = ? ORDER BY claimed_at DESC LIMIT 1');
$lastClaim->execute([$user['id']]);
$lastAt = $lastClaim->fetchColumn();

render_staff_layout($pdo, 'dashboard', 'Staff Dashboard', function () use ($batches, $todayCount, $lastAt): void {
    echo '<div class="page-header"><h1 class="page-title">Distribution Desk</h1></div>';
    echo '<div class="kpi-grid">';
    echo '<div class="card kpi-card"><div class="label">Scanned Today</div><div class="value">' . $todayCount . '</div></div>';
    echo '<div class="card kpi-card muted"><div class="label">Last Claim</div><div class="value value-sm">' . e($lastAt ? format_datetime($lastAt) : '—') . '</div></div>';
    echo '<div class="card kpi-card"><div class="label">Open Batches</div><div class="value">' . count($batches) . '</div></div>';
    echo '</div>';

    echo '<div class="card"><div class="card-title">Select active batch</div>';
    if (!$batches) {
        echo '<div class="empty-state">No open batches. Ask an admin to open a distribution batch.</div>';
    } else {
        echo '<form method="get" action="' . base_url('staff/scanner.php') . '" class="form-row">';
        echo '<select class="select" name="batch_id" required>';
        foreach ($batches as $b) {
            echo '<option value="' . (int) $b['id'] . '">' . e($b['name']) . '</option>';
        }
        echo '</select>';
        echo '<button class="btn btn-primary btn-md" type="submit">START SCANNING</button>';
        echo '</form>';
    }
    echo '</div>';
});
