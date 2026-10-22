<div class="breadcrumb">Admin / Programs</div>
<div class="page-header">
  <div>
    <h1 class="page-title">Scholarship Programs</h1>
    <p class="page-subtitle">Track enrolled scholars, linked distributions, and vouchers awaiting or completing claims.</p>
  </div>
  <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/program-form.php')) ?>">ADD PROGRAM</a>
</div>

<?php $portfolioAvailable = (float) ($overview['available_budget'] ?? 0); ?>
<div class="program-overview" aria-label="Program portfolio overview">
  <div class="program-overview-item"><span>Active programs</span><strong><?= (int) ($overview['active_programs'] ?? 0) ?></strong></div>
  <div class="program-overview-item"><span>Distribution batches</span><strong><?= (int) ($overview['batch_count'] ?? 0) ?></strong><small><?= (int) ($overview['open_batch_count'] ?? 0) ?> open</small></div>
  <div class="program-overview-item"><span>Total program budgets</span><strong><?= e(format_money((float) ($overview['total_budget'] ?? 0))) ?></strong></div>
  <div class="program-overview-item"><span><?= $portfolioAvailable < 0 ? 'Over committed' : 'Uncommitted' ?></span><strong><?= e(format_money(abs($portfolioAvailable))) ?></strong></div>
  <div class="program-overview-item"><span>Awaiting claims</span><strong><?= e(format_money((float) ($overview['pending_amount'] ?? 0))) ?></strong><small><?= (int) ($overview['pending_voucher_count'] ?? 0) ?> pending voucher<?= (int) ($overview['pending_voucher_count'] ?? 0) === 1 ? '' : 's' ?></small></div>
  <div class="program-overview-item"><span>Disbursed</span><strong><?= e(format_money((float) ($overview['claimed_amount'] ?? 0))) ?></strong><small><?= (int) ($overview['claimed_voucher_count'] ?? 0) ?> claimed voucher<?= (int) ($overview['claimed_voucher_count'] ?? 0) === 1 ? '' : 's' ?></small></div>
</div>
<p class="program-overview-note">Award amount is per scholar. Committed funds include pending and claimed vouchers; expired and void vouchers release their value.</p>

<?php
view('partials.datatable', [
    'tableId' => 'programs-table',
    'ajaxUrl' => base_url('api/admin/datatables/programs.php'),
    'columns' => ['Program / Cycle', 'Award / Scholar', 'Total Budget', 'Enrolled', 'Batches', 'Committed', 'Disbursed', 'Available', 'Status', 'Actions'],
    'defaultOrder' => [[0, 'asc']],
]);
?>
