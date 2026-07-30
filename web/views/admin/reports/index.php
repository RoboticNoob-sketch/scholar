<div class="breadcrumb">Admin / Reports</div>
<div class="page-header">
  <h1 class="page-title">Reports</h1>
  <div class="table-actions btn-group">
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/export-report.php?batch_id=' . $batchId)) ?>">EXPORT CSV</a>
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/export-report-pdf.php?batch_id=' . $batchId)) ?>">EXPORT PDF</a>
  </div>
</div>

<form class="filter-bar" id="report-filters" method="get">
  <div>
    <label class="field-label" for="batch_id">Batch</label>
    <select class="select" id="batch_id" name="batch_id" data-reload-page="1">
      <option value="0">All batches</option>
      <?php foreach ($batches as $batch): ?>
        <option value="<?= (int) $batch['id'] ?>"<?= $batchId === (int) $batch['id'] ? ' selected' : '' ?>>
          <?= e($batch['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="kpi-grid">
  <div class="card kpi-card">
    <div class="label">Total Claims</div>
    <div class="value" id="report-total-claims"><?= (int) $summary['total_claims'] ?></div>
  </div>
  <div class="card kpi-card">
    <div class="label">Total Disbursed</div>
    <div class="value" id="report-total-amount"><?= e(format_money($summary['total_amount'])) ?></div>
  </div>
</div>

<?php
view('partials.datatable', [
    'tableId' => 'reports-table',
    'ajaxUrl' => base_url('api/admin/datatables/reports.php'),
    'columns' => ['Date', 'Scholar', 'Program', 'Batch', 'Amount', 'Staff'],
    'filterForm' => '#report-batch-context',
    'defaultOrder' => [[0, 'desc']],
]);
?>
<div id="report-batch-context" hidden>
  <input type="hidden" name="batch_id" value="<?= (int) $batchId ?>">
</div>
