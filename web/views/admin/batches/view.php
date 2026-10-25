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
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="open"><button class="btn btn-primary btn-sm" type="submit">OPEN BATCH</button></form>
    <?php endif; ?>
    <?php if ($batch['status'] === 'draft' || $batch['status'] === 'open'): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="generate"><button class="btn btn-outline btn-sm" type="submit">GENERATE MISSING</button></form>
    <?php endif; ?>
    <?php if ((int) $counts['pending'] > 0 && $batch['status'] !== 'closed'): ?>
      <a class="btn btn-primary btn-sm" href="<?= e(base_url('admin/print-vouchers.php?id=' . (int) $batch['id'])) ?>"><i data-lucide="printer"></i> PRINT PENDING (<?= (int) $counts['pending'] ?>)</a>
    <?php endif; ?>
    <?php if ($batch['status'] === 'open'): ?>
      <button class="btn btn-secondary btn-sm" type="button" data-close-batch-open>CLOSE BATCH</button>
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
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="void">
  <div class="page-header">
    <div>
      <h2 class="page-title page-title-sm">Scholar Vouchers</h2>
      <p class="page-subtitle">One voucher per enrolled scholar. Existing voucher codes are kept and never regenerated.</p>
    </div>
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
      'columns' => ['', 'Scholar', 'Student No.', 'Voucher Code', 'Amount', 'Status', 'Claimed At'],
      'filterForm' => '#batch-voucher-filters',
      'defaultOrder' => [[1, 'asc']],
      'checkboxColumn' => 0,
  ]);
  ?>
</form>

<dialog class="voucher-dialog" id="voucher-preview-dialog" aria-labelledby="voucher-dialog-title">
  <div class="voucher-dialog-content">
    <div class="voucher-dialog-header">
      <div>
        <div class="panel-eyebrow">Distribution voucher</div>
        <h2 id="voucher-dialog-title">Voucher preview</h2>
      </div>
      <button class="icon-btn" type="button" data-voucher-close aria-label="Close voucher preview"><i data-lucide="x"></i></button>
    </div>
    <div class="voucher-preview-card">
      <div class="voucher-qr-frame" id="voucher-qr" aria-label="Voucher QR code"></div>
      <div class="voucher-preview-details">
        <span class="voucher-preview-label">Voucher code</span>
        <code class="voucher-preview-code" id="voucher-preview-code"></code>
        <div class="voucher-preview-name" id="voucher-preview-name"></div>
        <div class="voucher-preview-student" id="voucher-preview-student"></div>
        <div class="voucher-preview-amount" id="voucher-preview-amount"></div>
        <div class="voucher-preview-payload">QR payload: <code id="voucher-preview-payload"></code></div>
      </div>
    </div>
    <div class="voucher-dialog-actions">
      <button class="btn btn-outline btn-sm" type="button" data-voucher-copy><i data-lucide="copy"></i> Copy code</button>
      <button class="btn btn-primary btn-sm" type="button" data-voucher-close>Done</button>
    </div>
  </div>
</dialog>

<?php if ($batch['status'] === 'open'): ?>
<dialog class="voucher-dialog close-batch-dialog" id="close-batch-dialog" aria-labelledby="close-batch-title">
  <form method="post" class="voucher-dialog-content">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="close">
    <div class="voucher-dialog-header">
      <div>
        <div class="panel-eyebrow">Sign off batch</div>
        <h2 id="close-batch-title">Close “<?= e($batch['name']) ?>”?</h2>
      </div>
      <button class="icon-btn" type="button" data-close-batch-cancel aria-label="Cancel"><i data-lucide="x"></i></button>
    </div>
    <?php if ((int) $counts['pending'] > 0): ?>
      <div class="close-batch-warning is-alert">
        <i data-lucide="triangle-alert"></i>
        <div>
          <strong><?= (int) $counts['pending'] ?> scholar<?= (int) $counts['pending'] === 1 ? '' : 's' ?> have not claimed yet.</strong>
          Their vouchers will be marked <em>expired</em> and can no longer be scanned or redeemed.
        </div>
      </div>
    <?php else: ?>
      <div class="close-batch-warning">
        <i data-lucide="check-circle-2"></i>
        <div>Every voucher in this batch is already claimed or voided.</div>
      </div>
    <?php endif; ?>
    <ul class="close-batch-list">
      <li>Closing is final. A closed batch cannot be reopened.</li>
      <li>No more vouchers can be generated, voided or claimed.</li>
      <li>Claimed: <strong><?= (int) $counts['claimed'] ?></strong> · Pending: <strong><?= (int) $counts['pending'] ?></strong> · Void: <strong><?= (int) $counts['void'] ?></strong></li>
    </ul>
    <label class="close-batch-confirm">
      <input type="checkbox" name="confirm_close" value="1" data-close-batch-check>
      <span>I understand and want to sign off this batch.</span>
    </label>
    <div class="voucher-dialog-actions">
      <button class="btn btn-outline btn-sm" type="button" data-close-batch-cancel>Keep open</button>
      <button class="btn btn-primary btn-sm" type="submit" data-close-batch-submit disabled>Close &amp; sign off</button>
    </div>
  </form>
</dialog>
<script src="<?= e(asset_url('assets/js/batch-close.js')) ?>"></script>
<?php endif; ?>

<?php if ((int) $counts['total'] === 0): ?>
  <div class="empty-state">No vouchers generated yet — confirm eligible scholars to generate.</div>
<?php endif; ?>
