<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$stats = [
    'scholars' => (int) $pdo->query('SELECT COUNT(*) FROM scholars WHERE status = "active"')->fetchColumn(),
    'programs' => (int) $pdo->query('SELECT COUNT(*) FROM scholarship_programs WHERE status = "active"')->fetchColumn(),
    'open_batches' => (int) $pdo->query('SELECT COUNT(*) FROM distribution_batches WHERE status = "open"')->fetchColumn(),
    'claims_today' => (int) $pdo->query('SELECT COUNT(*) FROM claims WHERE DATE(claimed_at) = CURDATE()')->fetchColumn(),
];

$openBatch = $pdo->query(
    'SELECT b.*, p.name AS program_name, p.amount
     FROM distribution_batches b
     JOIN scholarship_programs p ON p.id = b.program_id
     WHERE b.status = "open"
     ORDER BY b.distribution_date DESC LIMIT 1'
)->fetch();

$chart = ['claimed' => 0, 'pending' => 0];
if ($openBatch) {
    $stmt = $pdo->prepare(
        'SELECT status, COUNT(*) AS cnt FROM claim_vouchers WHERE batch_id = ? GROUP BY status'
    );
    $stmt->execute([$openBatch['id']]);
    foreach ($stmt->fetchAll() as $row) {
        if ($row['status'] === 'claimed') {
            $chart['claimed'] = (int) $row['cnt'];
        } elseif ($row['status'] === 'pending') {
            $chart['pending'] = (int) $row['cnt'];
        }
    }
}

$activity = $pdo->query(
    'SELECT a.*, u.username FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC LIMIT 8'
)->fetchAll();

render_admin_layout($pdo, 'dashboard', 'Dashboard', function () use ($stats, $openBatch, $chart, $activity): void {
    echo '<div class="page-header"><h1 class="page-title">Dashboard</h1>';
    echo '<div class="table-actions">';
    echo '<a class="btn btn-outline btn-sm" href="' . base_url('admin/reports.php') . '">EXPORT REPORT</a>';
    echo '<a class="btn btn-primary btn-sm" href="' . base_url('admin/batch-form.php') . '">CREATE BATCH</a>';
    echo '</div></div>';

    echo '<div class="kpi-grid">';
    $cards = [
        ['Total Scholars', (string) $stats['scholars'], 'Active recipients', ''],
        ['Active Programs', (string) $stats['programs'], 'Current cycle', ''],
        ['Open Batches', (string) $stats['open_batches'], 'Distribution ready', 'warning'],
        ['Claims Today', (string) $stats['claims_today'], 'Recorded redemptions', 'muted'],
    ];
    foreach ($cards as [$label, $value, $delta, $tone]) {
        $toneClass = $tone ? ' ' . e($tone) : '';
        echo '<div class="card kpi-card' . $toneClass . '"><div class="label">' . e($label) . '</div>';
        echo '<div class="value">' . e($value) . '</div><div class="delta' . ($tone === 'muted' ? ' delta-muted' : '') . '">' . e($delta) . '</div></div>';
    }
    echo '</div>';

    echo '<div class="split-grid">';
    echo '<div class="card"><div class="card-title">';
    echo $openBatch ? e('Claimed vs Pending — ' . $openBatch['name']) : 'No open batch';
    echo '</div>';
    if ($openBatch) {
        $total = max(1, $chart['claimed'] + $chart['pending']);
        $claimedPct = (int) round(($chart['claimed'] / $total) * 100);
        $pendingPct = 100 - $claimedPct;
        echo '<div class="chart-bars">';
        echo '<div class="chart-bar"><div class="chart-bar-fill" style="height:' . $claimedPct . '%"></div><div class="chart-bar-label">Claimed (' . $chart['claimed'] . ')</div></div>';
        echo '<div class="chart-bar"><div class="chart-bar-fill warning" style="height:' . $pendingPct . '%"></div><div class="chart-bar-label">Pending (' . $chart['pending'] . ')</div></div>';
        echo '</div>';
    } else {
        echo '<div class="empty-state">Create and open a distribution batch to monitor claims.</div>';
    }
    echo '</div>';

    echo '<div class="card"><div class="card-title">Recent Activity</div>';
    if (!$activity) {
        echo '<div class="empty-state">No activity yet.</div>';
    }
    foreach ($activity as $item) {
        echo '<div class="activity-item"><div class="activity-top">';
        echo '<span class="activity-who">' . e($item['username'] ?? 'System') . '</span>';
        echo '<span class="badge badge-accent">' . e(str_replace('_', ' ', $item['action'])) . '</span>';
        echo '</div><div class="activity-meta">' . e($item['details'] ?? $item['entity_type']) . ' · ' . e(format_datetime($item['created_at'])) . '</div></div>';
    }
    echo '</div></div>';
});
