<div class="breadcrumb">Admin / Dashboard</div>
<div class="page-header dashboard-header">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Scholarship distribution overview for <?= e(format_date(date('Y-m-d'))) ?></p>
  </div>
  <div class="table-actions btn-group">
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/reports.php')) ?>">VIEW REPORTS</a>
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/export-report.php')) ?>">EXPORT CSV</a>
    <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/batch-form.php')) ?>">CREATE BATCH</a>
  </div>
</div>

<div class="kpi-grid dashboard-kpi">
  <?php foreach ($kpis as $kpi): ?>
    <a class="card kpi-card kpi-card-link<?= !empty($kpi['tone']) ? ' ' . e($kpi['tone']) : '' ?>" href="<?= e(base_url($kpi['href'])) ?>">
      <div class="label"><?= e($kpi['label']) ?></div>
      <div class="value"><?= e($kpi['value']) ?></div>
      <div class="delta<?= !empty($kpi['muted']) ? ' delta-muted' : '' ?>"><?= e($kpi['hint']) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="split-grid dashboard-split">
  <div class="card">
    <div class="card-title">Open batch progress</div>
    <?php if (!$batchProgress): ?>
      <div class="empty-state">No open batches. Create and open a distribution batch to monitor claims.</div>
    <?php else: ?>
      <div class="batch-progress-list">
        <?php foreach ($batchProgress as $batch): ?>
          <?php
            $total = max(1, (int) $batch['total']);
            $claimedPct = (int) round(((int) $batch['claimed'] / $total) * 100);
          ?>
          <div class="batch-progress-item">
            <div class="batch-progress-top">
              <strong><?= e($batch['name']) ?></strong>
              <span><?= e($batch['program_name']) ?></span>
            </div>
            <div class="progress-track">
              <div class="progress-fill" style="width: <?= $claimedPct ?>%"></div>
            </div>
            <div class="batch-progress-meta">
              Claimed <?= (int) $batch['claimed'] ?> · Pending <?= (int) $batch['pending'] ?> · <?= $claimedPct ?>%
              <a class="link-action" href="<?= e(base_url('admin/batch-view.php?id=' . (int) $batch['id'])) ?>">View</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title">Recent claims</div>
    <?php if (!$recentClaims): ?>
      <div class="empty-state">No claims recorded yet.</div>
    <?php else: ?>
      <?php foreach ($recentClaims as $claim): ?>
        <div class="activity-item">
          <div class="activity-top">
            <span class="activity-who"><?= e(scholar_full_name($claim)) ?></span>
            <span class="badge badge-accent"><?= e(format_money((float) $claim['amount'])) ?></span>
          </div>
          <div class="activity-meta">
            <?= e($claim['batch_name']) ?> · <?= e($claim['staff_name']) ?> · <?= e(format_datetime($claim['claimed_at'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="card dashboard-activity">
  <div class="card-title">Recent activity</div>
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
