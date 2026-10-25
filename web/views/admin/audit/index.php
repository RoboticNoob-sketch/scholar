<?php
use App\Queries\AuditQueries;

$dateFrom = date('Y-m-d', strtotime('-6 days'));
$presetAction = (string) ($_GET['action'] ?? '');
?>
<div class="breadcrumb">Admin / Audit Logs</div>
<div class="page-header"><h1 class="page-title">Audit Logs</h1></div>

<form class="filter-bar" id="audit-filters" method="get" onsubmit="return false">
  <div>
    <label class="field-label" for="audit-period">Period</label>
    <select class="select" id="audit-period" data-audit-period>
      <option value="today">Today</option>
      <option value="7" selected>Last 7 days</option>
      <option value="30">Last 30 days</option>
      <option value="90">Last 90 days</option>
      <option value="all">All time</option>
      <option value="custom">Custom range</option>
    </select>
  </div>
  <div>
    <label class="field-label" for="audit-from">From</label>
    <input class="input" type="date" id="audit-from" name="date_from" value="<?= e($dateFrom) ?>" data-audit-date>
  </div>
  <div>
    <label class="field-label" for="audit-to">To</label>
    <input class="input" type="date" id="audit-to" name="date_to" data-audit-date>
  </div>
  <div>
    <label class="field-label" for="audit-action">Action</label>
    <select class="select" id="audit-action" name="action">
      <option value="">All actions</option>
      <option value="failures"<?= $presetAction === 'failures' ? ' selected' : '' ?>>Failures &amp; rejections only</option>
      <?php foreach (AuditQueries::actionGroups() as $key => $group): ?>
        <optgroup label="<?= e($group['label']) ?>">
          <option value="group:<?= e($key) ?>">All <?= e(strtolower($group['label'])) ?></option>
          <?php foreach ($group['actions'] as $action => $label): ?>
            <option value="<?= e($action) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </optgroup>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="field-label" for="audit-user">User</label>
    <input class="input" type="search" id="audit-user" name="user" placeholder="Username, or “system”" autocomplete="off">
  </div>
  <div>
    <button class="btn btn-secondary btn-sm" type="button" data-audit-reset>Reset</button>
  </div>
</form>

<?php
view('partials.datatable', [
    'tableId' => 'audit-table',
    'ajaxUrl' => base_url('api/admin/datatables/audit.php'),
    'columns' => ['Time', 'User', 'Action', 'Details', 'IP'],
    'filterForm' => '#audit-filters',
    'defaultOrder' => [[0, 'desc']],
]);
?>
<script src="<?= e(asset_url('assets/js/audit-filters.js')) ?>"></script>
