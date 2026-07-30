<div class="breadcrumb">Admin / Audit Logs</div>
<div class="page-header"><h1 class="page-title">Audit Logs</h1></div>

<?php
view('partials.datatable', [
    'tableId' => 'audit-table',
    'ajaxUrl' => base_url('api/admin/datatables/audit.php'),
    'columns' => ['Time', 'User', 'Action', 'Details', 'IP'],
    'defaultOrder' => [[0, 'desc']],
]);
?>
