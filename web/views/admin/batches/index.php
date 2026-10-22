<div class="breadcrumb">Admin / Distribution Batches</div>
<div class="page-header">
  <div>
    <h1 class="page-title">Distribution Batches</h1>
    <?php if ($programId > 0): ?>
      <p class="page-subtitle">Showing batches for <?= e($programName) ?> · <a href="<?= e(base_url('admin/batches.php')) ?>">Clear filter</a></p>
    <?php endif; ?>
  </div>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/batch-form.php')) ?>">CREATE BATCH</a>
</div>

<?php if ($programId > 0): ?>
  <div id="batch-program-filter" hidden><input type="hidden" name="program_id" value="<?= $programId ?>"></div>
<?php endif; ?>

<?php
view('partials.datatable', [
    'tableId' => 'batches-table',
    'ajaxUrl' => base_url('api/admin/datatables/batches.php'),
    'columns' => ['Batch', 'Program', 'Date', 'Venue', 'Status', 'Actions'],
    'filterForm' => $programId > 0 ? '#batch-program-filter' : null,
    'defaultOrder' => [[2, 'desc']],
]);
?>
