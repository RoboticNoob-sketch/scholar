<div class="breadcrumb">Admin / Users</div>
<div class="page-header">
  <h1 class="page-title">User Accounts</h1>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/user-form.php')) ?>">ADD USER</a>
</div>

<div class="kpi-grid">
  <?php foreach ($statsCards as $card): ?>
    <div class="card kpi-card<?= !empty($card['tone']) ? ' ' . e($card['tone']) : '' ?>">
      <div class="label"><?= e($card['label']) ?></div>
      <div class="value"><?= (int) $card['value'] ?></div>
    </div>
  <?php endforeach; ?>
</div>

<form class="filter-bar" id="user-filters" method="get">
  <div>
    <label class="field-label" for="role">Role</label>
    <select class="select" id="role" name="role">
      <option value="">All roles</option>
      <?php foreach (['admin', 'staff', 'student'] as $role): ?>
        <option value="<?= e($role) ?>"<?= $roleFilter === $role ? ' selected' : '' ?>><?= e(role_label($role)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="field-label" for="status">Status</label>
    <select class="select" id="status" name="status">
      <option value="">All statuses</option>
      <?php foreach (['active', 'inactive'] as $status): ?>
        <option value="<?= e($status) ?>"<?= $statusFilter === $status ? ' selected' : '' ?>><?= e(ucfirst($status)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<?php
view('partials.datatable', [
    'tableId' => 'users-table',
    'ajaxUrl' => base_url('api/admin/datatables/users.php'),
    'columns' => ['Username', 'Role', 'Status', 'Linked profile', 'Created', 'Actions'],
    'filterForm' => '#user-filters',
    'defaultOrder' => [[0, 'asc']],
]);
?>

<div class="table-meta table-meta-note">Student logins are also created from the Scholars form</div>
