<div class="breadcrumb">Admin / Distribution Batches / <?= e($batch['name']) ?></div>
<div class="page-header">
  <div>
    <h1 class="page-title"><?= e($batch['name']) ?></h1>
    <div class="page-subtitle">
      <?= e($batch['program_name']) ?> · <?= e($batch['venue']) ?> · <?= e(format_date($batch['distribution_date'])) ?>
    </div>
  </div>
  <div class="table-actions btn-group">
    <span class="badge <?= e(badge_class($batch['status'])) ?>"><?= e($batch['status']) ?></span>
    <?php if ($batch['status'] === 'draft'): ?>
      <form method="post"><input type="hidden" name="action" value="open"><button class="btn btn-primary btn-sm" type="submit">OPEN BATCH</button></form>
    <?php endif; ?>
    <?php if ($batch['status'] === 'draft' || $batch['status'] === 'open'): ?>
      <form method="post"><input type="hidden" name="action" value="generate"><button class="btn btn-outline btn-sm" type="submit">GENERATE VOUCHERS</button></form>
    <?php endif; ?>
    <?php if ($batch['status'] === 'open'): ?>
      <form method="post"><input type="hidden" name="action" value="close"><button class="btn btn-secondary btn-sm" type="submit">CLOSE BATCH</button></form>
    <?php endif; ?>
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/export-batch.php?id=' . (int) $batch['id'])) ?>">EXPORT CSV</a>
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/export-batch-pdf.php?id=' . (int) $batch['id'])) ?>">EXPORT PDF</a>
  </div>
</div>

<div class="kpi-grid">
  <?php foreach ($countCards as $card): ?>
    <div class="card kpi-card<?= !empty($card['tone']) ? ' ' . e($card['tone']) : '' ?>">
      <div class="label"><?= e($card['label']) ?></div>
      <div class="value"><?= (int) $card['value'] ?></div>
    </div>
  <?php endforeach; ?>
</div>

<form method="post" id="batch-void-form">
  <input type="hidden" name="action" value="void">
  <div class="page-header">
    <h2 class="page-title page-title-sm">Scholar Vouchers</h2>
    <?php if ($batch['status'] !== 'closed'): ?>
      <button class="btn btn-outline btn-sm" type="submit">VOID SELECTED</button>
    <?php endif; ?>
  </div>

  <div id="batch-voucher-filters" hidden>
    <input type="hidden" name="batch_id" value="<?= (int) $batch['id'] ?>">
  </div>

  <?php
  view('partials.datatable', [
      'tableId' => 'vouchers-table',
      'ajaxUrl' => base_url('api/admin/datatables/vouchers.php'),
      'columns' => ['', 'Scholar', 'Student No.', 'Amount', 'Status', 'Claimed At'],
      'filterForm' => '#batch-voucher-filters',
      'defaultOrder' => [[1, 'asc']],
      'checkboxColumn' => 0,
  ]);
  ?>
</form>

<?php if ((int) $counts['total'] === 0): ?>
  <div class="empty-state">No vouchers generated yet — confirm eligible scholars to generate.</div>
<?php endif; ?>
