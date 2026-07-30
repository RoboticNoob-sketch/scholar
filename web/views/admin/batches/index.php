<div class="breadcrumb">Admin / Distribution Batches</div>
<div class="page-header">
  <h1 class="page-title">Distribution Batches</h1>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/batch-form.php')) ?>">CREATE BATCH</a>
</div>

<?php
view('partials.datatable', [
    'tableId' => 'batches-table',
    'ajaxUrl' => base_url('api/admin/datatables/batches.php'),
    'columns' => ['Batch', 'Program', 'Date', 'Venue', 'Status', 'Actions'],
    'defaultOrder' => [[2, 'desc']],
]);
?>
