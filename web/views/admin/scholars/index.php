<div class="breadcrumb">Admin / Scholars</div>
<div class="page-header">
  <h1 class="page-title">Scholars</h1>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/scholar-form.php')) ?>">ADD SCHOLAR</a>
</div>

<form class="filter-bar" id="scholar-filters" method="get">
  <div>
    <label class="field-label" for="program_id">Program</label>
    <select class="select" id="program_id" name="program_id">
      <option value="0">All programs</option>
      <?php foreach ($programs as $program): ?>
        <option value="<?= (int) $program['id'] ?>"<?= $programId === (int) $program['id'] ? ' selected' : '' ?>>
          <?= e($program['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<?php
view('partials.datatable', [
    'tableId' => 'scholars-table',
    'ajaxUrl' => base_url('api/admin/datatables/scholars.php'),
    'columns' => ['Student No.', 'Name', 'Program', 'Status', 'Actions'],
    'filterForm' => '#scholar-filters',
    'defaultOrder' => [[1, 'asc']],
]);
?>
