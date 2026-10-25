<?php
$statusLabels = [
    'submitted' => 'Submitted',
    'under_review' => 'Under review',
    'needs_info' => 'More information needed',
    'approved' => 'Approved',
    'declined' => 'Not selected',
];
  $schoolOptions = ['SLSU Tiaong Campus'];
  $courseOptions = ['BS Information Technology', 'BS Computer Science', 'STEM', 'ABM', 'HUMSS', 'GAS', 'TVL'];
  $schoolValue = (string) ($editing['school_name'] ?? '');
  $schoolChoice = in_array($schoolValue, $schoolOptions, true) ? $schoolValue : ($schoolValue !== '' ? 'Other' : '');
  $courseValue = (string) ($editing['course'] ?? '');
  $courseChoice = in_array($courseValue, $courseOptions, true) ? $courseValue : ($courseValue !== '' ? 'Other' : '');
  $hasGeneralRegistration = false;
  foreach ($applications as $application) {
    if (empty($application['program_id'])) {
      $hasGeneralRegistration = true;
      break;
    }
  }
?>
<header class="public-nav">
  <a class="public-brand" href="<?= e(base_url('index.php')) ?>" aria-label="Scholarly home">
    <span class="public-brand-icon"><i data-lucide="graduation-cap"></i></span>
    <span>Scholarly<span class="public-brand-dot">.</span><small>STUDENT PORTAL</small></span>
  </a>
  <nav class="student-nav-links" aria-label="Student navigation">
    <a href="<?= e(base_url('index.php#programs')) ?>">Programs</a>
    <a class="public-nav-login" href="<?= e(base_url('logout.php')) ?>">Sign out <i data-lucide="log-out"></i></a>
  </nav>
</header>

<main class="student-portal">
  <section class="student-portal-heading">
    <div>
      <div class="public-section-kicker">STUDENT INTAKE</div>
      <h1>Your scholar registration</h1>
      <p>Signed in as <?= e($user['email'] ?? '') ?></p>
    </div>
    <a class="student-back-link" href="<?= e(base_url('index.php#programs')) ?>"><i data-lucide="arrow-left"></i> View programs</a>
  </section>

  <?php render_flash(); ?>

  <section class="student-application-layout">
    <div class="student-applications-list">
      <div class="student-subheading"><div><span>REGISTRATION STATUS</span><h2>Track your progress</h2></div><strong><?= count($applications) ?></strong></div>
      <?php if ($applications === []): ?>
        <div class="student-empty-state"><i data-lucide="file-check-2"></i><h3>Not registered yet</h3><p>Submit one scholar registration. Program assignment happens after the SK office approves your profile.</p></div>
      <?php else: ?>
        <?php foreach ($applications as $application): ?>
          <article class="student-application-item">
            <div class="student-application-item-top">
              <div><span class="student-application-cycle"><?= empty($application['program_id']) ? 'GENERAL REGISTRATION' : e($application['semester'] . ' | ' . $application['academic_year']) ?></span><h3><?= e($application['program_name']) ?></h3></div>
              <span class="student-status student-status-<?= e($application['status']) ?>"><?= e($statusLabels[$application['status']] ?? $application['status']) ?></span>
            </div>
            <div class="student-application-meta"><span>Submitted <?= e(format_datetime($application['created_at'])) ?></span><?php if (!empty($application['program_id'])): ?><strong><?= e(format_money((float) $application['amount'])) ?> per scholar</strong><?php else: ?><strong>Program assignment after approval</strong><?php endif; ?></div>
            <div class="student-documents-status"><span>2x2 photo: <?= empty($application['photo_2x2_path']) ? 'Missing' : 'Uploaded' ?></span><span>Enrollment form: <?= empty($application['enrollment_form_path']) ? 'Missing' : 'Uploaded' ?></span></div>
            <?php if ($application['status'] === 'approved' && !empty($application['scholar_id'])): ?><div class="student-review-note"><strong>Next step</strong><p>Your scholar profile is ready. The SK office can consider you for any active program; each program assignment is approved separately.</p></div><?php elseif ($application['status'] === 'approved' && (empty($application['photo_2x2_path']) || empty($application['enrollment_form_path']))): ?><div class="student-review-note"><strong>Next step</strong><p>The SK office will request any missing documents before preparing your scholar profile.</p></div><?php elseif ($application['status'] === 'approved'): ?><div class="student-review-note"><strong>Next step</strong><p>You are approved as a potential scholar. The SK office will create your profile; program assignment comes later.</p></div><?php endif; ?>
            <?php if (!empty($application['review_note'])): ?>
              <div class="student-review-note"><strong>SK office note</strong><p><?= nl2br(e($application['review_note'])) ?></p></div>
            <?php endif; ?>
            <?php if ($application['status'] === 'needs_info'): ?>
              <a class="student-update-link" href="<?= e(base_url('student/applications.php?edit=' . (int) $application['id'])) ?>">Update registration <i data-lucide="arrow-up-right"></i></a>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <aside class="student-application-form-wrap">
      <div class="student-subheading"><div><span><?= $editing ? 'UPDATE REQUESTED DETAILS' : 'ONE-TIME REGISTRATION' ?></span><h2><?= $editing ? 'Complete your response' : 'Register as a potential scholar' ?></h2></div></div>
      <?php if ($editing): ?>
        <div class="student-editing-program"><strong><?= e($editing['program_name'] ?? 'General scholar registration') ?></strong><span>Update the requested details and resubmit for review.</span></div>
      <?php endif; ?>
      <?php if (!$editing && $hasGeneralRegistration): ?>
        <div class="student-no-programs">Your one-time scholar registration is already on file. Track its status on this page; the SK office will assign an eligible program after approval.</div>
      <?php else: ?>
        <form method="post" enctype="multipart/form-data" class="student-application-form">
          <?= csrf_field() ?>
          <?php if ($editing): ?>
            <input type="hidden" name="application_id" value="<?= (int) $editing['id'] ?>">
          <?php endif; ?>
          <div class="student-fields-two">
            <label class="student-field"><span>First name</span><input name="first_name" maxlength="80" value="<?= e($editing['first_name'] ?? '') ?>" autocomplete="given-name" required></label>
            <label class="student-field"><span>Last name</span><input name="last_name" maxlength="80" value="<?= e($editing['last_name'] ?? '') ?>" autocomplete="family-name" required></label>
          </div>
          <label class="student-field"><span>Contact number</span><input name="phone" type="tel" maxlength="40" value="<?= e($editing['phone'] ?? '') ?>" autocomplete="tel" required></label>
          <label class="student-field"><span>School</span><select name="school_choice" data-other-choice="school" required><option value="">Choose a school</option><?php foreach ($schoolOptions as $option): ?><option value="<?= e($option) ?>" <?= $schoolChoice === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?><option value="Other" <?= $schoolChoice === 'Other' ? 'selected' : '' ?>>Other</option></select></label>
          <label class="student-field" data-other-field="school" <?= $schoolChoice === 'Other' ? '' : 'hidden' ?>><span>Enter school name</span><input name="school_other" maxlength="160" value="<?= $schoolChoice === 'Other' ? e($schoolValue) : '' ?>" <?= $schoolChoice === 'Other' ? 'required' : '' ?>></label>
          <label class="student-field"><span>Course or strand</span><select name="course_choice" data-other-choice="course" required><option value="">Choose a course or strand</option><?php foreach ($courseOptions as $option): ?><option value="<?= e($option) ?>" <?= $courseChoice === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?><option value="Other" <?= $courseChoice === 'Other' ? 'selected' : '' ?>>Other</option></select></label>
          <label class="student-field" data-other-field="course" <?= $courseChoice === 'Other' ? '' : 'hidden' ?>><span>Enter course or strand</span><input name="course_other" maxlength="120" value="<?= $courseChoice === 'Other' ? e($courseValue) : '' ?>" <?= $courseChoice === 'Other' ? 'required' : '' ?>></label>
          <label class="student-field"><span>Year level</span><input name="year_level" maxlength="40" value="<?= e($editing['year_level'] ?? '') ?>" placeholder="For example, 1st year" required></label>
          <label class="student-field"><span>Note to the SK office <small>Optional</small></span><textarea name="application_note" rows="4" maxlength="2000"><?= e($editing['application_note'] ?? '') ?></textarea></label>
          <label class="student-field"><span>2x2 ID photo</span><?php if (!empty($editing['photo_2x2_path'])): ?><small>Photo already uploaded. Leave blank to keep it or choose a replacement.</small><?php endif; ?><input type="file" name="photo_2x2" accept=".jpg,.jpeg,.png,image/jpeg,image/png" <?= empty($editing['photo_2x2_path']) ? 'required' : '' ?>><small>Square JPG or PNG, at least 300 × 300 pixels, up to 2 MB.</small></label>
          <label class="student-field"><span>Enrollment form</span><?php if (!empty($editing['enrollment_form_path'])): ?><small>Form already uploaded. Leave blank to keep it or choose a replacement.</small><?php endif; ?><input type="file" name="enrollment_form" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" <?= empty($editing['enrollment_form_path']) ? 'required' : '' ?>><small>PDF, JPG, or PNG, up to 5 MB.</small></label>
          <p class="student-privacy-note">These documents are private and available only to authorized SK administrators for application review. No other KYC documents are requested.</p>
          <label class="student-consent"><input type="checkbox" name="privacy_acknowledged" value="1" required <?= $editing ? 'checked' : '' ?>><span>I confirm these details are accurate and agree that the SK office may use my application details and uploaded documents to review this application and contact me about it.</span></label>
          <button class="public-button-primary" type="submit"><?= $editing ? 'Resubmit registration' : 'Submit scholar registration' ?> <i data-lucide="arrow-right"></i></button>
        </form>
      <?php endif; ?>
    </aside>
  </section>
</main>

<footer class="public-footer">
  <a class="public-brand" href="<?= e(base_url('index.php')) ?>"><span class="public-brand-icon"><i data-lucide="graduation-cap"></i></span><span>Scholarly<span class="public-brand-dot">.</span><small>COMMUNITY SCHOLARSHIPS</small></span></a>
  <span>Scholar registration is one-time. Program assignments and funding approvals are handled separately.</span>
  <a href="<?= e(base_url('logout.php')) ?>">Sign out</a>
</footer>