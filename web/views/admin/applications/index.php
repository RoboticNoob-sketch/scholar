<?php
$statusLabels = [
    'submitted' => 'Submitted',
    'under_review' => 'Under review',
    'needs_info' => 'Needs information',
    'approved' => 'Approved',
    'declined' => 'Not selected',
];
$statusOptions = ['' => 'All statuses'] + $statusLabels;
?>
<section class="page-heading">
  <div><div class="eyebrow">STUDENT INTAKE</div><h1>Applications</h1><p>Review and track student submissions.</p></div>
</section>
<?php render_flash(); ?>
<div class="application-review-notice"><i data-lucide="info"></i><span>Application approval does not create enrollment or a voucher. Program enrollment and funding remain in the separate approver workflow.</span></div>
<div class="application-review-counts">
  <?php foreach (['submitted' => 'New', 'under_review' => 'In review', 'needs_info' => 'Needs info', 'approved' => 'Approved', 'declined' => 'Not selected'] as $status => $label): ?>
    <a href="<?= e(base_url('admin/applications.php?status=' . $status)) ?>" class="<?= $statusFilter === $status ? 'is-active' : '' ?>"><span><?= e($label) ?></span><strong><?= (int) ($statusCounts[$status] ?? 0) ?></strong></a>
  <?php endforeach; ?>
</div>
<form class="application-filter-bar" method="get">
  <label><span>Status</span><select name="status"><?php foreach ($statusOptions as $value => $label): ?><option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
  <button class="btn btn-secondary" type="submit"><i data-lucide="list-filter"></i> Filter</button>
  <?php if ($statusFilter !== ''): ?><a class="application-clear-filter" href="<?= e(base_url('admin/applications.php')) ?>">Clear filter</a><?php endif; ?>
</form>

<?php if ($applications === []): ?>
  <div class="card application-review-empty"><i data-lucide="inbox"></i><h2>No applications<?= $statusFilter !== '' ? ' in this status' : ' yet' ?></h2><p>Student submissions will appear here for review.</p></div>
<?php else: ?>
  <div class="card table-card application-queue-card">
    <div class="table-wrap">
      <table id="application-queue" class="table datatable-client application-queue-table" data-page-length="10" data-default-order='[[3,"desc"]]' data-export-name="student-applications" width="100%">
        <thead><tr><th>Student</th><th>Registration</th><th>Status</th><th>Submitted</th><th>Documents</th><th>Review</th></tr></thead>
        <tbody>
          <?php foreach ($applications as $application): ?>
            <tr>
              <td><div class="application-queue-student"><strong><?= e($application['first_name'] . ' ' . $application['last_name']) ?></strong><span><?= e($application['email']) ?></span></div></td>
              <td><div class="application-queue-program"><strong><?= e($application['program_name']) ?></strong><span><?= !empty($application['program_id']) ? e($application['semester'] . ' | ' . $application['academic_year']) : 'Program assignment after approval' ?></span></div></td>
              <td><span class="application-review-status application-review-status-<?= e($application['status']) ?>"><?= e($statusLabels[$application['status']] ?? $application['status']) ?></span></td>
              <td data-order="<?= e((string) strtotime($application['created_at'])) ?>"><?= e(format_datetime($application['created_at'])) ?></td>
              <td><div class="application-queue-documents">
                <?php if (!empty($application['photo_2x2_path'])): ?><a aria-label="Review 2x2 photo" title="Review 2x2 photo" href="<?= e(base_url('admin/application-document.php?id=' . (int) $application['id'] . '&document=photo_2x2')) ?>" target="_blank" rel="noopener"><i data-lucide="image"></i></a><?php else: ?><span class="application-doc-missing" title="2x2 photo missing"><i data-lucide="image-off"></i></span><?php endif; ?>
                <?php if (!empty($application['enrollment_form_path'])): ?><a aria-label="Review enrollment form" title="Review enrollment form" href="<?= e(base_url('admin/application-document.php?id=' . (int) $application['id'] . '&document=enrollment_form')) ?>" target="_blank" rel="noopener"><i data-lucide="file-text"></i></a><?php else: ?><span class="application-doc-missing" title="Enrollment form missing"><i data-lucide="file-warning"></i></span><?php endif; ?>
              </div></td>
              <td>
                <?php if ($application['scholar_id']): ?>
                  <a class="application-view-scholar" href="<?= e(base_url('admin/scholar-view.php?id=' . (int) $application['scholar_id'])) ?>"><i data-lucide="user-round-check"></i> Scholar</a>
                <?php else: ?>
                  <details class="application-row-review">
                    <summary><i data-lucide="clipboard-list"></i> Review</summary>
                    <div class="application-row-review-panel">
                      <header class="application-review-head">
                        <div><strong><?= e($application['first_name'] . ' ' . $application['last_name']) ?></strong><span><?= e($application['email']) ?> &middot; Submitted <?= e(format_datetime($application['created_at'])) ?></span></div>
                        <span class="application-review-status application-review-status-<?= e($application['status']) ?>"><?= e($statusLabels[$application['status']] ?? $application['status']) ?></span>
                        <button type="button" class="application-review-close" data-review-close aria-label="Close review"><i data-lucide="x"></i></button>
                      </header>
                      <?php
                        $docUrl = static fn (string $doc): string => base_url('admin/application-document.php?id=' . (int) $application['id'] . '&document=' . $doc);
                        $formIsPdf = str_ends_with(strtolower((string) $application['enrollment_form_path']), '.pdf');
                      ?>
                      <div class="application-review-docs">
                        <figure>
                          <figcaption><span>2x2 photo / ID</span><?php if (!empty($application['photo_2x2_path'])): ?><a href="<?= e($docUrl('photo_2x2')) ?>" target="_blank" rel="noopener">Open full size</a><?php endif; ?></figcaption>
                          <?php if (!empty($application['photo_2x2_path'])): ?>
                            <a href="<?= e($docUrl('photo_2x2')) ?>" target="_blank" rel="noopener"><img loading="lazy" alt="2x2 photo of <?= e($application['full_name'] ?? 'student') ?>" src="<?= e($docUrl('photo_2x2')) ?>"></a>
                          <?php else: ?><div class="application-review-doc-missing">Not uploaded</div><?php endif; ?>
                        </figure>
                        <figure>
                          <figcaption><span>Enrollment form</span><?php if (!empty($application['enrollment_form_path'])): ?><a href="<?= e($docUrl('enrollment_form')) ?>" target="_blank" rel="noopener">Open full size</a><?php endif; ?></figcaption>
                          <?php if (empty($application['enrollment_form_path'])): ?>
                            <div class="application-review-doc-missing">Not uploaded</div>
                          <?php elseif ($formIsPdf): ?>
                            <iframe loading="lazy" title="Enrollment form" src="<?= e($docUrl('enrollment_form')) ?>"></iframe>
                          <?php else: ?>
                            <a href="<?= e($docUrl('enrollment_form')) ?>" target="_blank" rel="noopener"><img loading="lazy" alt="Enrollment form" src="<?= e($docUrl('enrollment_form')) ?>"></a>
                          <?php endif; ?>
                        </figure>
                      </div>
                      <div class="application-review-main">
                      <dl class="application-review-details">
                        <div><dt>Phone</dt><dd><?= e($application['phone']) ?></dd></div>
                        <div><dt>School</dt><dd><?= e($application['school_name']) ?></dd></div>
                        <div><dt>Course / strand</dt><dd><?= e($application['course']) ?></dd></div>
                        <div><dt>Year level</dt><dd><?= e($application['year_level']) ?></dd></div>
                      </dl>
                      <?php if (!empty($application['application_note'])): ?><div class="application-review-student-note"><strong>Student note</strong><p><?= nl2br(e($application['application_note'])) ?></p></div><?php endif; ?>
                      <?php if ($application['status'] === 'approved' && (empty($application['photo_2x2_path']) || empty($application['enrollment_form_path']))): ?>
                        <div class="application-documents-needed"><i data-lucide="triangle-alert"></i><span>Both documents are required to approve and register this scholar. Request the missing files from the student first.</span></div>
                      <?php endif; ?>
                      <form method="post" class="application-review-form">
                        <?= csrf_field() ?><input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                        <div class="application-quick-decisions" role="group" aria-label="Quick decision">
                          <button type="button" data-quick-decision="approved"><i data-lucide="check-circle-2"></i> Approve</button>
                          <button type="button" data-quick-decision="needs_info"><i data-lucide="message-circle-question"></i> Request info</button>
                          <button type="button" data-quick-decision="declined"><i data-lucide="x-circle"></i> Decline</button>
                        </div>
                        <label><span>Decision</span><select name="status" required><?php foreach (['under_review' => 'Keep in review', 'needs_info' => 'Request information', 'approved' => 'Approve and create scholar profile', 'declined' => 'Decline registration'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $application['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                        <label data-student-no-field hidden><span>Official student number <small>Required when approving</small></span><input name="student_no" maxlength="32" placeholder="Enter the official student number"></label>
                        <label><span>Note to student / internal note <small data-note-hint></small></span><textarea name="review_note" rows="3" maxlength="1000" placeholder="Required when requesting information" data-review-note><?= e($application['review_note'] ?? '') ?></textarea></label>
                        <button class="btn btn-primary" type="submit"><i data-lucide="check"></i> Save decision</button>
                      </form>
                                              </div>
                                            </div>
                  </details>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<script src="<?= e(asset_url('assets/js/application-review.js')) ?>"></script>