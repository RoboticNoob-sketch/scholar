<?php
$hasFilters = $filters['period'] !== '' || $filters['program_id'] || $filters['batch_id'] || $filters['staff_id'];
$trendMax = max(1, ...array_map(static fn($t) => (int) $t['n'], $trend ?: [['n' => 1]]));
$breakMax = max(1.0, ...array_map(static fn($b) => (float) $b['amt'], $breakdown ?: [['amt' => 1]]));
$periods = ['' => 'All time', 'today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month', 'custom' => 'Custom range'];
?>
<div class="breadcrumb">Admin / Reports</div>
<div class="page-header">
  <h1 class="page-title">Reports</h1>
  <div class="table-actions btn-group">
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/export-report.php?' . $exportQuery)) ?>">EXPORT CSV</a>
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/export-report-pdf.php?' . $exportQuery)) ?>">EXPORT PDF</a>
  </div>
</div>

<form class="filter-bar" id="report-filters" method="get" data-report-filters>
  <div>
    <label class="field-label" for="rp-period">Period</label>
    <select class="select" id="rp-period" name="period">
      <?php foreach ($periods as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= $filters['period'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="field-label" for="rp-from">From</label>
    <input class="input" type="date" id="rp-from" name="date_from" value="<?= e($filters['date_from']) ?>">
  </div>
  <div>
    <label class="field-label" for="rp-to">To</label>
    <input class="input" type="date" id="rp-to" name="date_to" value="<?= e($filters['date_to']) ?>">
  </div>
  <div>
    <label class="field-label" for="rp-program">Program</label>
    <select class="select" id="rp-program" name="program_id">
      <option value="0">All programs</option>
      <?php foreach ($programs as $p): ?>
        <option value="<?= (int) $p['id'] ?>"<?= $filters['program_id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="field-label" for="rp-batch">Batch</label>
    <select class="select" id="rp-batch" name="batch_id">
      <option value="0">All batches</option>
      <?php foreach ($batches as $b): ?>
        <option value="<?= (int) $b['id'] ?>"         data-program="<?= (int) $b['program_id'] ?>"<?= $filters['batch_id'] === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="field-label" for="rp-staff">Processed by</label>
    <select class="select" id="rp-staff" name="staff_id">
      <option value="0">All staff</option>
      <?php foreach ($staff as $s): ?>
        <option value="<?= (int) $s['id'] ?>"<?= $filters['staff_id'] === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['username']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <a class="btn btn-secondary btn-sm<?= $hasFilters ? '' : ' is-disabled' ?>" href="<?= e(base_url('admin/reports.php')) ?>">Reset</a>
  </div>
</form>

<div class="kpi-grid">
  <div class="card kpi-card">
    <div class="label">Total Claims</div>
    <div class="value"><?= (int) $summary['total_claims'] ?></div>
    <div class="kpi-sub"><?= (int) $summary['scholars'] ?> scholar<?= $summary['scholars'] === 1 ? '' : 's' ?></div>
  </div>
  <div class="card kpi-card">
    <div class="label">Total Disbursed</div>
    <div class="value"><?= e(format_money($summary['total_amount'])) ?></div>
    <div class="kpi-sub">Avg <?= e(format_money($summary['average'])) ?> per claim</div>
  </div>
  <div class="card kpi-card">
    <div class="label">Claim Rate</div>
    <div class="value"><?= (int) $summary['claim_rate'] ?>%</div>
    <div class="kpi-sub"><?= (int) $summary['issued'] ?> vouchers issued</div>
  </div>
  <div class="card kpi-card">
    <div class="label">Unclaimed</div>
    <div class="value"><?= (int) $summary['pending'] ?></div>
    <div class="kpi-sub"><?= (int) $summary['expired'] ?> expired</div>
  </div>
</div>

<div class="report-grid">
  <div class="card dashboard-panel">
    <div class="card-title-row">
      <div class="card-title"><i data-lucide="trending-up"></i> Claims per day</div>
      <span class="grid-live">Last <?= count($trend) ?> active day<?= count($trend) === 1 ? '' : 's' ?></span>
    </div>
    <?php if (!$trend): ?>
      <div class="empty-state">No claims match these filters.</div>
    <?php else: ?>
      <div class="dash-bars" role="img" aria-label="Claims per day">
        <?php foreach ($trend as $t): ?>
          <div class="dash-bar-col" title="<?= e(format_date($t['d'])) ?>: <?= (int) $t['n'] ?> claims · <?= e(format_money((float) $t['amt'])) ?>">
            <span class="dash-bar-n"><?= (int) $t['n'] ?></span>
            <span class="dash-bar" style="height: <?= max(3, (int) round($t['n'] / $trendMax * 100)) ?>%"></span>
            <span class="dash-bar-d"><?= e(date('M j', strtotime($t['d']))) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card dashboard-panel">
    <div class="card-title-row">
      <div class="card-title"><i data-lucide="pie-chart"></i> Disbursed by program</div>
    </div>
    <?php if (!$breakdown): ?>
      <div class="empty-state">No claims match these filters.</div>
    <?php else: ?>
      <div class="report-breakdown">
        <?php foreach ($breakdown as $b): ?>
          <div class="report-break-row">
            <div class="report-break-head">
              <span><?= e($b['program_name']) ?></span>
              <strong><?= e(format_money((float) $b['amt'])) ?></strong>
            </div>
            <div class="report-break-track"><span style="width: <?= max(2, (int) round($b['amt'] / $breakMax * 100)) ?>%"></span></div>
            <div class="report-break-sub"><?= (int) $b['n'] ?> claim<?= (int) $b['n'] === 1 ? '' : 's' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
view('partials.datatable', [
    'tableId' => 'reports-table',
    'ajaxUrl' => base_url('api/admin/datatables/reports.php'),
    'columns' => ['Date', 'Scholar', 'Program', 'Batch', 'Amount', 'Staff'],
    'filterForm' => '#report-filters',
    'defaultOrder' => [[0, 'desc']],
]);
?>
<script src="<?= e(asset_url('assets/js/report-filters.js')) ?>"></script>
