<div class="breadcrumb">Admin / My Requests</div>
<div class="page-header">
  <div>
    <h1 class="page-title">My Requests</h1>
    <p class="page-subtitle">Track program and batch submissions through independent review.</p>
  </div>
  <a class="btn btn-outline btn-sm" href="<?= e(base_url('admin/user-form.php')) ?>"><i data-lucide="user-plus"></i> Add approver</a>
</div>

<?php if ($requests === []): ?>
  <div class="empty-state">You have not submitted any approval requests.</div>
<?php else: ?>
  <div class="approval-list">
    <?php foreach ($requests as $request): ?>
      <?php
        $payload = $request['request_payload'];
        $typeLabels = ['program_create' => 'New program', 'program_update' => 'Program change', 'batch_create' => 'New batch', 'scholar_enrollment_update' => 'Scholar enrollment'];
        $requestName = $request['request_type'] === 'scholar_enrollment_update' ? $request['scholar_name'] : ($payload['name'] ?? '');
      ?>
      <article class="approval-history-row">
        <div>
          <span class="panel-eyebrow">Request #<?= (int) $request['id'] ?> · <?= e(format_datetime($request['created_at'])) ?></span>
          <strong><?= e($typeLabels[$request['request_type']] ?? 'Change request') ?> · <?= e($requestName) ?></strong>
          <?php if ($request['review_note']): ?><p><?= e($request['review_note']) ?></p><?php endif; ?>
          <?php if ($request['reviewer_name']): ?><small>Reviewed by <?= e($request['reviewer_name']) ?><?= $request['reviewed_at'] ? ' · ' . e(format_datetime($request['reviewed_at'])) : '' ?></small><?php endif; ?>
        </div>
        <span class="badge <?= e(badge_class($request['status'])) ?>"><?= e($request['status']) ?></span>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>