<?php
$programCount = count($programs);
?>
<header class="public-nav">
  <a class="public-brand" href="<?= e(base_url('index.php')) ?>" aria-label="Scholarly home">
    <span class="public-brand-icon"><i data-lucide="graduation-cap"></i></span>
    <span>Scholarly<span class="public-brand-dot">.</span><small>COMMUNITY SCHOLARSHIPS</small></span>
  </a>
  <nav aria-label="Main navigation">
    <a href="#programs">Programs</a>
    <a href="#how-it-works">How it works</a>
    <a class="public-nav-login" href="<?= e(base_url('login.php')) ?>">Staff sign in <i data-lucide="arrow-up-right"></i></a>
  </nav>
</header>

<main>
  <section class="public-hero" aria-labelledby="public-hero-title">
    <div class="public-hero-image" role="img" aria-label="Students learning together in a campus study space"></div>
    <div class="public-hero-content">
      <div class="public-eyebrow"><span></span> SCHOLARSHIP ASSISTANCE | COMMUNITY PROGRAMS</div>
      <h1 id="public-hero-title">Scholarship support,<br><em>closer to home.</em></h1>
      <p>Explore education assistance programs offered through your local Sangguniang Kabataan. Clear information for students, careful stewardship for the community.</p>
      <div class="public-hero-actions">
        <a class="public-button-primary" href="<?= e(base_url('auth/google-start.php')) ?>">Apply with Google <i data-lucide="arrow-up-right"></i></a>
        <span class="public-app-status"><i data-lucide="<?= $googleConfigured ? 'circle-check' : 'clock-3' ?>"></i> <?= $googleConfigured ? 'Google sign-in is ready' : 'Google sign-in setup in progress' ?></span>
      </div>
      <div class="public-hero-footnote"><?= $googleConfigured ? 'Applications are reviewed by the SK office. Contact them to confirm current requirements and deadlines.' : 'Online applications will open once Google sign-in is connected. Contact your SK office for current requirements and deadlines.' ?></div>
    </div>
    <div class="public-hero-index"><span>01</span> EDUCATION SUPPORT <i></i> LOCAL ACTION</div>
  </section>

  <section class="public-program-section" id="programs" aria-labelledby="programs-title">
    <div class="public-section-heading">
      <div>
        <div class="public-section-kicker">ACTIVE SCHOLARSHIP LISTINGS</div>
        <h2 id="programs-title">Find the right support.</h2>
      </div>
      <p><?= $programCount ?> <?= $programCount === 1 ? 'program' : 'programs' ?> currently listed</p>
    </div>

    <?php if ($programs === []): ?>
      <div class="public-empty-programs">There are no active scholarship listings right now. Please check back or contact your SK office.</div>
    <?php else: ?>
      <div class="public-program-grid">
        <?php foreach ($programs as $index => $program): ?>
          <article class="public-program-card">
            <div class="public-program-topline"><span><?= sprintf('%02d', $index + 1) ?> / <?= e($program['academic_year']) ?></span><span class="public-program-mark"><i data-lucide="graduation-cap"></i></span></div>
            <h3><?= e($program['name']) ?></h3>
            <p><?= e(trim((string) ($program['description'] ?? '')) ?: 'Scholarship assistance program. Contact your SK office for eligibility requirements and application dates.') ?></p>
            <div class="public-program-award"><span>AWARD PER SCHOLAR</span><strong><?= e(format_money((float) $program['amount'])) ?></strong></div>
            <div class="public-program-cycle"><i data-lucide="calendar-days"></i><?= e($program['semester']) ?> | <?= e($program['academic_year']) ?></div>
            <div class="public-program-availability"><span></span> Listing active | Confirm application schedule with your SK office</div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p class="public-program-disclaimer">Program listings do not guarantee eligibility or funding. Final requirements, deadlines, and selection are confirmed by the barangay.</p>
  </section>

  <section class="public-process" id="how-it-works" aria-labelledby="how-title">
    <div class="public-process-heading">
      <div class="public-section-kicker">A CLEARER PATH TO SUPPORT</div>
      <h2 id="how-title">What to expect</h2>
    </div>
    <div class="public-process-steps">
      <article><span>01</span><h3>Explore</h3><p>Review the active programs and note the award amount and cycle.</p></article>
      <article><span>02</span><h3>Confirm</h3><p>Ask your SK office about current eligibility, documents, and application dates.</p></article>
      <article><span>03</span><h3>Apply in person</h3><p>Until online registration launches, submit your application through the barangay's announced process.</p></article>
    </div>
  </section>
</main>

<footer class="public-footer">
  <a class="public-brand" href="<?= e(base_url('index.php')) ?>">
    <span class="public-brand-icon"><i data-lucide="graduation-cap"></i></span>
    <span>Scholarly<span class="public-brand-dot">.</span><small>COMMUNITY SCHOLARSHIPS</small></span>
  </a>
  <span>Supporting students through transparent local scholarship programs.</span>
  <a href="<?= e(base_url('login.php')) ?>">Staff sign in</a>
</footer>