<div class="breadcrumb">Approver / Approval Queue</div>
<div class="page-header">
  <div>
    <h1 class="page-title">Approval Queue</h1>
    <p class="page-subtitle">Review submitted program and batch changes. You cannot approve a request you submitted.</p>
  </div>
  <span class="card-badge"><?= count($requests) ?> pending</span>
</div>

<?php if ($requests === []): ?>
  <div class="empty-state">The queue is clear. New program and batch requests will appear here.</div>
<?php else: ?>
  <div class="approval-list">
    <?php foreach ($requests as $request): ?>
      <?php
        $payload = $request['request_payload'];
        $typeLabels = ['program_create' => 'New program', 'program_update' => 'Program change', 'batch_create' => 'New distribution batch'];
        $typeLabel = $typeLabels[$request['request_type']] ?? 'Change request';
      ?>
      <article class="approval-card">
        <header class="approval-card-header">
          <div>
            <div class="panel-eyebrow">Request #<?= (int) $request['id'] ?> · <?= e(format_datetime($request['created_at'])) ?></div>
            <h2><?= e($typeLabel) ?><?= $request['request_type'] === 'program_update' ? ' · ' . e($request['program_context']['name'] ?? 'Program') : '' ?></h2>
            <p>Submitted by <strong><?= e($request['submitter_name']) ?></strong></p>
          </div>
          <span class="badge badge-warning">pending review</span>
        </header>

        <?php if (str_starts_with($request['request_type'], 'program_')): ?>
          <dl class="approval-snapshot">
            <div><dt>Program</dt><dd><?= e($payload['name'] ?? $request['program_context']['name'] ?? '') ?></dd></div>
            <div><dt>Award per scholar</dt><dd><?= e(format_money((float) ($payload['amount'] ?? 0))) ?></dd></div>
            <div><dt>Total budget</dt><dd><?= e(format_money((float) ($payload['total_budget'] ?? 0))) ?></dd></div>
            <div><dt>Cycle</dt><dd><?= e(($payload['academic_year'] ?? '') . ' · ' . ($payload['semester'] ?? '')) ?></dd></div>
            <div><dt>Enrolled scholars</dt><dd><?= count($payload['scholar_ids'] ?? []) ?></dd></div>
            <div><dt>Status</dt><dd><?= e($payload['status'] ?? '') ?></dd></div>
          </dl>
        <?php elseif ($request['request_type'] === 'scholar_enrollment_update'): ?>
          <dl class="approval-snapshot">
            <div><dt>Scholar</dt><dd><?= e($request['scholar_context'] ? scholar_full_name($request['scholar_context']) : 'Scholar unavailable') ?></dd></div>
            <div><dt>Student number</dt><dd><?= e($request['scholar_context']['student_no'] ?? '') ?></dd></div>
            <div><dt>Requested programs</dt><dd><?= $request['program_names'] ? e(implode(', ', $request['program_names'])) : 'Remove from all programs' ?></dd></div>
          </dl>
        <?php else: ?>
          <dl class="approval-snapshot">
            <div><dt>Batch</dt><dd><?= e($payload['name'] ?? '') ?></dd></div>
            <div><dt>Program</dt><dd><?= e($request['program_context']['name'] ?? 'Program unavailable') ?></dd></div>
            <div><dt>Distribution</dt><dd><?= e(format_date($payload['distribution_date'] ?? null)) ?> · <?= e($payload['venue'] ?? '') ?></dd></div>
            <div><dt>Voucher generation</dt><dd><?= !empty($payload['generate_vouchers']) ? 'Requested' : 'Not requested' ?></dd></div>
            <?php if (!empty($payload['generate_vouchers']) && $request['program_context']): ?>
              <?php $projectedAvailable = (float) $request['program_context']['total_budget'] - (float) $request['program_context']['current_commitment'] - (float) $request['program_context']['estimated_voucher_amount']; ?>
              <div><dt>Estimated voucher total</dt><dd><?= e(format_money((float) $request['program_context']['estimated_voucher_amount'])) ?> · <?= (int) $request['program_context']['eligible_count'] ?> scholars</dd></div>
              <div><dt>Projected budget</dt><dd><?= (float) $request['program_context']['total_budget'] > 0 ? ($projectedAvailable >= 0 ? e(format_money($projectedAvailable)) . ' remaining' : 'Over by ' . e(format_money(abs($projectedAvailable)))) : 'Budget not set' ?></dd></div>
            <?php endif; ?>
          </dl>
        <?php endif; ?>

        <form method="post" class="approval-review-form">
          <?= csrf_field() ?>
          <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
          <label class="approval-review-note">
            <span>Review note (required when rejecting)</span>
            <input class="input" name="review_note" maxlength="1000" placeholder="Reason or review notes">
          </label>
          <div class="btn-group">
            <button class="btn btn-danger btn-sm" type="submit" name="decision" value="rejected"><i data-lucide="x"></i> Reject</button>
            <button class="btn btn-primary btn-sm" type="submit" name="decision" value="approved"><i data-lucide="check"></i> Approve</button>
          </div>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>