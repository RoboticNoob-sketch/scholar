<div class="breadcrumb">Admin / Overview</div>
<div class="page-header dashboard-header">
  <div>
    <div class="dashboard-eyebrow">Scholarship operations</div>
    <h1 class="page-title">Overview</h1>
    <p class="page-subtitle">Distribution status and claim activity · <?= e(format_date(date('Y-m-d'))) ?></p>
  </div>
  <div class="table-actions btn-group">
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/reports.php')) ?>"><i data-lucide="chart-no-axes-column-increasing"></i> Reports</a>
    <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/batch-form.php')) ?>"><i data-lucide="plus"></i> Create batch</a>
  </div>
</div>

<div class="kpi-grid dashboard-kpi">
  <?php foreach ($kpis as $kpi): ?>
    <a class="card kpi-card kpi-card-link<?= !empty($kpi['tone']) ? ' ' . e($kpi['tone']) : '' ?>" href="<?= e(base_url($kpi['href'])) ?>">
      <div class="kpi-head">
        <div class="label"><?= e($kpi['label']) ?></div>
        <span class="kpi-icon"><i data-lucide="<?= e($kpi['icon'] ?? 'activity') ?>"></i></span>
      </div>
      <div class="value"><?= e($kpi['value']) ?></div>
      <div class="delta<?= !empty($kpi['muted']) ? ' delta-muted' : '' ?>"><?= e($kpi['hint']) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="split-grid dashboard-split">
  <div class="card dashboard-panel">
    <div class="card-title-row">
      <div class="card-title"><i data-lucide="package"></i> Open batch progress</div>
      <?php if ($batchProgress): ?>
        <span class="card-badge"><?= count($batchProgress) ?> open</span>
      <?php endif; ?>
    </div>
    <?php if (!$batchProgress): ?>
      <div class="empty-state">
        <i data-lucide="inbox"></i>
        No open batches. Create and open a distribution batch to monitor claims.
      </div>
    <?php else: ?>
      <div class="batch-progress-list">
        <?php foreach ($batchProgress as $batch): ?>
          <?php
            $total = (int) $batch['total'];
            $claimed = (int) $batch['claimed'];
            $pending = (int) $batch['pending'];
            $denominator = max(1, $total);
            $claimedPct = (int) round(($claimed / $denominator) * 100);
            $isComplete = $total > 0 && $pending === 0;
          ?>
          <a class="batch-progress-card<?= $isComplete ? ' is-complete' : '' ?>" href="<?= e(base_url('admin/batch-view.php?id=' . (int) $batch['id'])) ?>">
            <div class="batch-progress-head">
              <div class="batch-progress-info">
                <span class="batch-progress-name"><?= e($batch['name']) ?></span>
                <span class="batch-progress-program">
                  <i data-lucide="graduation-cap"></i>
                  <?= e($batch['program_name']) ?>
                </span>
                <?php if (!empty($batch['distribution_date'])): ?>
                  <span class="batch-progress-date">
                    <i data-lucide="calendar"></i>
                    <?= e(format_date($batch['distribution_date'])) ?>
                  </span>
                <?php endif; ?>
              </div>
              <span class="batch-progress-pct"><?= $claimedPct ?>%</span>
            </div>
            <div class="progress-track" role="progressbar" aria-valuenow="<?= $claimedPct ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="progress-fill" style="width: <?= $claimedPct ?>%"></div>
            </div>
            <div class="batch-progress-stats">
              <span class="batch-stat batch-stat-claimed">
                <strong><?= $claimed ?></strong> claimed
              </span>
              <span class="batch-stat batch-stat-pending">
                <strong><?= $pending ?></strong> pending
              </span>
              <span class="batch-stat batch-stat-total">
                <strong><?= $total ?></strong> vouchers
              </span>
              <span class="batch-progress-action">View batch <i data-lucide="arrow-right"></i></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card dashboard-panel dashboard-grid-panel">
    <div class="card-title-row">
      <div>
        <div class="panel-eyebrow">Claim ledger</div>
        <div class="card-title"><i data-lucide="receipt"></i> Recent claims</div>
      </div>
      <span class="grid-live">Latest activity</span>
    </div>
    <?php if (!$recentClaims): ?>
      <div class="empty-state">No claims recorded yet.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table id="dashboard-claims" class="table dashboard-claims-table datatable-client" data-default-order="[[3, &quot;desc&quot;]]" data-page-length="5" data-export-name="recent-claims">
          <thead>
            <tr><th>Scholar</th><th>Distribution</th><th>Amount</th><th>Claimed at</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentClaims as $claim): ?>
              <tr>
                <td>
                  <span class="grid-scholar-name"><?= e(scholar_full_name($claim)) ?></span>
                  <span class="grid-scholar-meta"><?= e($claim['student_no']) ?></span>
                </td>
                <td>
                  <span class="grid-cell-main"><?= e($claim['batch_name']) ?></span>
                  <span class="grid-cell-sub">Handled by <?= e($claim['staff_name']) ?></span>
                </td>
                <td data-order="<?= e((string) $claim['amount']) ?>"><span class="grid-amount"><?= e(format_money((float) $claim['amount'])) ?></span></td>
                <td data-order="<?= e((string) strtotime($claim['claimed_at'])) ?>"><span class="grid-time"><?= e(format_datetime($claim['claimed_at'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card dashboard-panel dashboard-activity">
  <div class="card-title-row">
    <div class="card-title"><i data-lucide="activity"></i> Recent activity</div>
  </div>
  <?php if (!$activity): ?>
    <div class="empty-state">No activity yet.</div>
  <?php else: ?>
    <?php foreach ($activity as $item): ?>
      <div class="activity-item">
        <div class="activity-top">
          <span class="activity-who"><?= e($item['username'] ?? 'System') ?></span>
          <span class="badge badge-accent"><?= e(str_replace('_', ' ', $item['action'])) ?></span>
        </div>
        <div class="activity-meta">
          <?= e($item['details'] ?? $item['entity_type']) ?> · <?= e(format_datetime($item['created_at'])) ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
