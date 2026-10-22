<?php

$budget = (float) $program['total_budget'];
$committed = (float) $program['committed_amount'];
$claimed = (float) $program['claimed_amount'];
$pending = (float) $program['pending_amount'];
$available = $budget - $committed;
$hasBudget = $budget > 0;
$isOverBudget = $hasBudget && $available < 0;
$claimedPercent = $budget > 0 ? min(100, ($claimed / $budget) * 100) : 0;
$pendingPercent = $budget > 0 ? min(100 - $claimedPercent, ($pending / $budget) * 100) : 0;
$availablePercent = max(0, 100 - $claimedPercent - $pendingPercent);
?>
<div class="breadcrumb"><a href="<?= e(base_url('admin/programs.php')) ?>">Admin / Programs</a> / Money flow</div>
<div class="page-header program-flow-header">
  <div>
    <div class="dashboard-eyebrow">Program money flow</div>
    <h1 class="page-title"><?= e($program['name']) ?></h1>
    <p class="page-subtitle"><?= e($program['academic_year']) ?> · <?= e($program['semester']) ?> · Award <?= e(format_money((float) $program['amount'])) ?> per scholar</p>
  </div>
  <div class="table-actions btn-group">
    <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/programs.php')) ?>"><i data-lucide="arrow-left"></i> All programs</a>
    <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/program-form.php?id=' . (int) $program['id'])) ?>"><i data-lucide="pencil"></i> Edit program</a>
  </div>
</div>

<div class="program-flow-kpis">
  <div class="program-flow-kpi"><span>Total budget</span><strong><?= e(format_money($budget)) ?></strong></div>
  <div class="program-flow-kpi<?= $isOverBudget ? ' is-over-budget' : '' ?>"><span><?= !$hasBudget ? 'Budget not set' : ($isOverBudget ? 'Over budget' : 'Available') ?></span><strong><?= $hasBudget ? e(format_money(abs($available))) : '—' ?></strong></div>
  <div class="program-flow-kpi"><span>Committed to vouchers</span><strong><?= e(format_money($committed)) ?></strong><small><?= (int) $program['pending_voucher_count'] + (int) $program['claimed_voucher_count'] ?> pending or claimed</small></div>
  <div class="program-flow-kpi"><span>Disbursed</span><strong><?= e(format_money($claimed)) ?></strong><small><?= (int) $program['claimed_voucher_count'] ?> claimed</small></div>
  <div class="program-flow-kpi"><span>Awaiting claim</span><strong><?= e(format_money($pending)) ?></strong><small><?= (int) $program['pending_voucher_count'] ?> pending</small></div>
</div>

<section class="program-flow-panel" aria-labelledby="budget-flow-title">
  <div class="program-flow-panel-head">
    <div>
      <div class="panel-eyebrow">Budget utilization</div>
      <h2 id="budget-flow-title">Where the funds are</h2>
    </div>
    <?php if ($budget <= 0): ?>
      <a class="link-action" href="<?= e(base_url('admin/program-form.php?id=' . (int) $program['id'])) ?>">Set program budget</a>
    <?php endif; ?>
  </div>
  <?php if ($budget > 0): ?>
    <div class="program-flow-bar" role="img" aria-label="<?= e(format_money($claimed)) ?> disbursed, <?= e(format_money($pending)) ?> awaiting claim, <?= e(format_money(max(0, $available))) ?> available">
      <?php if ($claimedPercent > 0): ?><span class="flow-segment is-claimed" style="width: <?= e((string) $claimedPercent) ?>%"></span><?php endif; ?>
      <?php if ($pendingPercent > 0): ?><span class="flow-segment is-pending" style="width: <?= e((string) $pendingPercent) ?>%"></span><?php endif; ?>
      <?php if ($availablePercent > 0): ?><span class="flow-segment is-available" style="width: <?= e((string) $availablePercent) ?>%"></span><?php endif; ?>
    </div>
    <div class="program-flow-legend">
      <span><i class="flow-dot is-claimed"></i> Disbursed <strong><?= e(format_money($claimed)) ?></strong></span>
      <span><i class="flow-dot is-pending"></i> Awaiting claim <strong><?= e(format_money($pending)) ?></strong></span>
      <span><i class="flow-dot is-available"></i> Uncommitted <strong><?= e(format_money(max(0, $available))) ?></strong></span>
    </div>
    <?php if ($isOverBudget): ?>
      <div class="program-budget-warning"><i data-lucide="triangle-alert"></i> Active vouchers exceed the program budget by <?= e(format_money(abs($available))) ?>. Review batches before issuing more vouchers.</div>
    <?php endif; ?>
  <?php else: ?>
    <p class="program-flow-empty-budget">No total program budget has been set. Add one to calculate uncommitted funds.</p>
  <?php endif; ?>
</section>

<section class="program-batch-flow" aria-labelledby="batch-flow-title">
  <div class="program-flow-panel-head">
    <div>
      <div class="panel-eyebrow">Distribution ledger</div>
      <h2 id="batch-flow-title">Money flow by batch</h2>
    </div>
    <?php $batchCount = count($batches); ?>
    <span class="card-badge"><?= $batchCount ?> <?= $batchCount === 1 ? 'batch' : 'batches' ?></span>
  </div>
  <?php if ($batches === []): ?>
    <div class="empty-state">No distribution batches are linked to this program yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table program-flow-table">
        <thead><tr><th>Batch</th><th>Status</th><th>Vouchers</th><th>Disbursed</th><th>Awaiting claim</th><th>Committed</th></tr></thead>
        <tbody>
          <?php foreach ($batches as $batch): ?>
            <tr>
              <td><a class="program-flow-batch-name" href="<?= e(base_url('admin/batch-view.php?id=' . (int) $batch['id'])) ?>"><?= e($batch['name']) ?></a><span class="program-flow-batch-meta"><?= e(format_date($batch['distribution_date'])) ?> · <?= e($batch['venue']) ?></span></td>
              <td><span class="badge <?= e(badge_class($batch['status'])) ?>"><?= e($batch['status']) ?></span></td>
              <td><?= (int) $batch['claimed_count'] + (int) $batch['pending_count'] ?><span class="program-flow-batch-meta"><?= (int) $batch['claimed_count'] ?> claimed · <?= (int) $batch['pending_count'] ?> pending</span></td>
              <td><?= e(format_money((float) $batch['claimed_amount'])) ?></td>
              <td><?= e(format_money((float) $batch['pending_amount'])) ?></td>
              <td class="program-flow-committed"><?= e(format_money((float) $batch['committed_amount'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>