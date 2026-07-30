<div class="breadcrumb">Admin / Programs</div>
<div class="page-header">
  <h1 class="page-title">Scholarship Programs</h1>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/program-form.php')) ?>">ADD PROGRAM</a>
</div>

<?php
view('partials.datatable', [
    'tableId' => 'programs-table',
    'ajaxUrl' => base_url('api/admin/datatables/programs.php'),
    'columns' => ['Program', 'Amount', 'Academic Year', 'Semester', 'Enrolled', 'Status', 'Actions'],
    'defaultOrder' => [[0, 'asc']],
]);
?>
