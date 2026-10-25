<?php
$programCount = count($programs);
?>
<header class="public-nav public-landing-nav">
  <a class="public-brand" href="<?= e(base_url('index.php')) ?>" aria-label="Scholarly home">
    <span class="public-brand-icon"><i data-lucide="graduation-cap"></i></span>
    <span>Scholarly<span class="public-brand-dot">.</span><small>SLSU TIAONG / CABAY</small></span>
  </a>
  <nav aria-label="Main navigation">
    <a href="#programs">Programs</a>
    <a href="#student-tools">How it works</a>
    <a href="#team">Our team</a>
    <a class="public-nav-login" href="<?= e(base_url('login.php')) ?>">Staff portal <i data-lucide="arrow-up-right"></i></a>
  </nav>
</header>

<main class="public-landing">
  <section class="public-hero" aria-labelledby="public-hero-title">
    <div class="public-hero-image" role="img" aria-label="Students learning together in a campus study space"></div>
    <div class="public-hero-content">
      <div class="public-eyebrow"><span></span> SLSU TIAONG · CAPSTONE PROJECT · BARANGAY CABAY</div>
      <p class="public-hero-pretitle">Local opportunity. Clear next steps.</p>
      <h1 id="public-hero-title">Scholarship support,<br><em>closer to home.</em></h1>
      <p class="public-hero-description">Explore opportunities from the Sangguniang Kabataan of Barangay Cabay, submit your registration, and follow your progress from the web or mobile app.</p>
      <div class="public-hero-actions">
        <?php if ($googleConfigured): ?>
          <a class="public-button-primary" href="<?= e(base_url('auth/google-start.php')) ?>">Start your registration <i data-lucide="arrow-up-right"></i></a>
          <a class="public-button-secondary" href="#programs">View opportunities <i data-lucide="arrow-down-right"></i></a>
        <?php else: ?>
          <a class="public-button-primary" href="#programs">View opportunities <i data-lucide="arrow-down-right"></i></a>
        <?php endif; ?>
      </div>
      <div class="public-hero-note"><i data-lucide="map-pin"></i><span>Barangay Cabay · Tiaong, Quezon</span><i class="public-hero-note-rule"></i><span>Built at SLSU Tiaong</span></div>
    </div>
    <div class="public-hero-edition"><div><strong><?= sprintf('%02d', $programCount) ?></strong><small>PROGRAMS LISTED</small></div><span>BARANGAY CABAY · TIAONG, QUEZON</span></div>
    <a class="public-hero-scroll" href="#programs"><span>SCROLL TO EXPLORE</span><i data-lucide="arrow-down"></i></a>
  </section>

  <section class="public-program-section" id="programs" aria-labelledby="programs-title" data-reveal>
    <div class="public-section-heading">
      <div>
        <div class="public-section-kicker">01 / CURRENT OPPORTUNITIES</div>
        <h2 id="programs-title">Find your next step.</h2>
      </div>
      <p class="public-section-count"><strong><?= sprintf('%02d', $programCount) ?></strong><span><?= $programCount === 1 ? 'program' : 'programs' ?> listed<br>for Cabay students</span></p>
    </div>

    <?php if ($programs === []): ?>
      <div class="public-empty-programs">There are no active scholarship listings right now. Please check back or contact your SK office.</div>
    <?php else: ?>
      <div class="public-program-grid">
        <?php foreach ($programs as $index => $program): ?>
          <?php
            $description = trim((string) ($program['description'] ?? ''));
            if ($description === '' || in_array(strtolower($description), ['test', 'todo', 'placeholder'], true)) {
              $description = 'Scholarship assistance offered through the Sangguniang Kabataan of Barangay Cabay. Contact the SK office for current eligibility and application dates.';
            }
          ?>
          <article class="public-program-card" data-reveal style="--reveal-delay: <?= min($index * 90, 360) ?>ms">
            <div class="public-program-topline"><span>OPPORTUNITY <?= sprintf('%02d', $index + 1) ?></span><span><?= e($program['academic_year']) ?></span></div>
            <span class="public-program-mark"><i data-lucide="graduation-cap"></i></span>
            <h3><?= e($program['name']) ?></h3>
            <p><?= e($description) ?></p>
            <div class="public-program-award"><span>AWARD PER SCHOLAR</span><strong><?= e(format_money((float) $program['amount'])) ?></strong></div>
            <div class="public-program-cycle"><i data-lucide="calendar-days"></i><?= e($program['semester']) ?> · <?= e($program['academic_year']) ?></div>
            <div class="public-program-availability"><span></span>Active listing <i></i> Confirm dates with the SK office</div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p class="public-program-disclaimer"><i data-lucide="info"></i> Listings are for guidance. Eligibility, schedules, selection, and final funding decisions are confirmed by the SK office.</p>
  </section>

  <section class="public-student-tools" id="student-tools" aria-labelledby="student-tools-title" data-reveal>
    <div class="public-student-tools-heading">
      <div>
        <div class="public-section-kicker">02 / FROM APPLICATION TO DISTRIBUTION</div>
        <h2 id="student-tools-title">Every step, in view.</h2>
      </div>
      <p>A connected web and mobile experience for students and the Sangguniang Kabataan of Barangay Cabay.</p>
    </div>
    <div class="public-student-tools-grid">
      <article data-reveal style="--reveal-delay: 0ms">
        <span class="public-student-tool-icon"><i data-lucide="file-check-2"></i></span>
        <span class="public-student-tool-index">01 / REGISTER</span>
        <h3>Start online</h3>
        <p>Sign in with Google, complete one registration, and submit your 2x2 photo and enrollment form.</p>
      </article>
      <article data-reveal style="--reveal-delay: 100ms">
        <span class="public-student-tool-icon"><i data-lucide="bell-ring"></i></span>
        <span class="public-student-tool-index">02 / FOLLOW</span>
        <h3>Know where you stand</h3>
        <p>See your current status and SK office notes. If more information is needed, update and resubmit your registration.</p>
      </article>
      <article data-reveal style="--reveal-delay: 200ms">
        <span class="public-student-tool-icon"><i data-lucide="scan-line"></i></span>
        <span class="public-student-tool-index">03 / RECEIVE</span>
        <h3>Ready for distribution</h3>
        <p>Once your scholar profile and voucher are set up, show the matching QR from the app at the distribution desk.</p>
      </article>
    </div>
    <aside class="public-app-download" id="android-app" aria-labelledby="android-app-title">
      <div class="public-app-download-copy">
        <span class="public-app-download-kicker"><i data-lucide="smartphone"></i> ANDROID APP</span>
        <h3 id="android-app-title">Your scholarship updates, on mobile.</h3>
        <p>Track registration progress, read SK office updates, view your profile, and show voucher QR codes when they are issued.</p>
      </div>
      <?php if ($mobileDownloadUrl !== ''): ?>
        <a class="public-button-primary" href="<?= e($mobileDownloadUrl) ?>" target="_blank" rel="noopener noreferrer">Download Android app <i data-lucide="download"></i></a>
      <?php else: ?>
        <span class="public-app-download-soon"><i data-lucide="clock-3"></i> Android download coming soon</span>
      <?php endif; ?>
    </aside>
  </section>

  <section class="public-team" id="team" aria-labelledby="team-title" data-reveal>
    <div class="public-team-heading">
      <div class="public-section-kicker">03 / THE PEOPLE BEHIND SCHOLARLY</div>
      <h2 id="team-title">A local project.<br>A student team.</h2>
      <p>Scholarly is a 4th-year capstone at SLSU Tiaong, created around a real community need in Barangay Cabay, Tiaong, Quezon.</p>
    </div>
    <div class="public-team-grid" aria-label="Capstone project team">
      <article class="public-team-member" data-reveal style="--reveal-delay: 0ms">
        <img class="public-team-photo" src="<?= e(base_url('assets/images/team/neil-garcia.jpg')) ?>" alt="Neil Jr. P. Garcia" loading="lazy">
        <div><span class="public-team-role">SLSU TIAONG · 4TH YEAR</span><h3>Garcia, Neil Jr. P.</h3></div>
      </article>
      <article class="public-team-member" data-reveal style="--reveal-delay: 90ms">
        <img class="public-team-photo" src="<?= e(base_url('assets/images/team/kier-dolor.jpg')) ?>" alt="Kier Dolor" loading="lazy">
        <div><span class="public-team-role">SLSU TIAONG · 4TH YEAR</span><h3>Dolor, Kier</h3></div>
      </article>
      <article class="public-team-member" data-reveal style="--reveal-delay: 180ms">
        <img class="public-team-photo" src="<?= e(base_url('assets/images/team/lray-aquino.jpg')) ?>" alt="LRay Aquino" loading="lazy">
        <div><span class="public-team-role">SLSU TIAONG · 4TH YEAR</span><h3>Aquino, LRay</h3></div>
      </article>
    </div>
    <p class="public-team-note">A student-led capstone prototype. Scholarship eligibility, selection, and funding decisions remain with the SK office.</p>
  </section>
</main>

<footer class="public-footer">
  <a class="public-brand" href="<?= e(base_url('index.php')) ?>">
    <span class="public-brand-icon"><i data-lucide="graduation-cap"></i></span>
    <span>Scholarly<span class="public-brand-dot">.</span><small>SLSU TIAONG · BRGY. CABAY</small></span>
  </a>
  <span>SLSU Tiaong capstone project · Sangguniang Kabataan, Barangay Cabay, Tiaong, Quezon</span>
  <a href="<?= e(base_url('login.php')) ?>">Staff sign in</a>
</footer>