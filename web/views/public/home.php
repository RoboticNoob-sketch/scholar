<?php
$programCount = count($programs);
$totalAward = array_sum(array_map(static fn (array $p): float => (float) $p['amount'], $programs));
$register = $googleConfigured ? base_url('auth/google-start.php') : base_url('login.php');
$registerLabel = $googleConfigured ? 'Start your registration' : 'Sign in';
$skLogoPath = __DIR__ . '/../../assets/images/sk-cabay-logo.png';
$skLogoUrl = is_file($skLogoPath) ? asset_url('assets/images/sk-cabay-logo.png') : '';
?>
<div class="lp">
<header class="lp-nav" data-lp-nav>
  <a class="lp-brand" href="<?= e(base_url('index.php')) ?>" aria-label="Scholarly home">
    <img src="<?= e(base_url('assets/images/slsu-tiaong-logo.png')) ?>" alt="" width="36" height="36">
    <?php if ($skLogoUrl !== ''): ?><img src="<?= e($skLogoUrl) ?>" alt="" width="36" height="36"><?php endif; ?>
    <span>Scholarly<b>.</b><small>SLSU Tiaong · Brgy. Cabay</small></span>
  </a>
  <button class="lp-nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="lp-menu" data-lp-toggle><i data-lucide="menu"></i></button>
  <nav class="lp-menu" id="lp-menu" aria-label="Main navigation">
    <a href="#how">How it works</a>
    <a href="#features">Features</a>
    <a href="#sk">SK Cabay</a>
    <a href="#programs">Programs</a>
    <a href="#faq">FAQ</a>
    <?php if ($mobileDownloadUrl !== ''): ?><a class="lp-menu-app" href="#app"><i data-lucide="smartphone"></i> Get the app</a><?php endif; ?>
    <a class="lp-menu-login" href="<?= e(base_url('login.php')) ?>">Staff portal</a>
    <a class="lp-btn lp-btn-primary lp-btn-sm" href="<?= e($register) ?>"><?= e($registerLabel) ?> <i data-lucide="arrow-up-right"></i></a>
  </nav>
</header>

<main>
  <section class="lp-hero" aria-labelledby="lp-hero-title">
    <div class="lp-hero-copy">
      <span class="lp-pill"><i data-lucide="map-pin"></i> Sangguniang Kabataan · Barangay Cabay, Tiaong, Quezon</span>
      <h1 id="lp-hero-title">Scholarship aid,<br><span>tracked from apply to claim.</span></h1>
      <p>Register online, follow your application, and claim your assistance with a secure QR voucher. One connected system for students, the SK office, and approvers.</p>
      <div class="lp-actions">
        <a class="lp-btn lp-btn-primary" href="<?= e($register) ?>"><?= e($registerLabel) ?> <i data-lucide="arrow-up-right"></i></a>
        <?php if ($mobileDownloadUrl !== ''): ?>
          <a class="lp-btn lp-btn-ghost" href="#app"><i data-lucide="smartphone"></i> Get the Android app</a>
        <?php else: ?>
          <a class="lp-btn lp-btn-ghost" href="#how">See how it works <i data-lucide="arrow-down"></i></a>
        <?php endif; ?>
      </div>
      <ul class="lp-hero-trust">
        <li><i data-lucide="shield-check"></i> Google sign-in</li>
        <li><i data-lucide="qr-code"></i> Single-use QR vouchers</li>
        <li><i data-lucide="smartphone"></i> Web + Android</li>
      </ul>
    </div>

    <div class="lp-hero-visual" aria-hidden="true">
      <div class="lp-phone">
        <div class="lp-phone-notch"></div>
        <div class="lp-phone-screen">
          <div class="lp-phone-head"><span>Claim voucher</span><em>READY</em></div>
          <div class="lp-qr">
            <?php
              $n = 21;
              $finder = static function (int $r, int $c, int $or, int $oc): ?bool {
                  $dr = $r - $or; $dc = $c - $oc;
                  if ($dr < 0 || $dr > 6 || $dc < 0 || $dc > 6) return null;
                  return $dr === 0 || $dr === 6 || $dc === 0 || $dc === 6 || ($dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4);
              };
              for ($r = 0; $r < $n; $r++) {
                  for ($c = 0; $c < $n; $c++) {
                      $f = $finder($r, $c, 0, 0) ?? $finder($r, $c, 0, 14) ?? $finder($r, $c, 14, 0);
                      $reserved = ($r < 8 && $c < 8) || ($r < 8 && $c > 12) || ($r > 12 && $c < 8);
                      $on = $f ?? (!$reserved && (($r * 7 + $c * 13 + ($r * $c) % 5) % 3 !== 0));
                      echo '<i', $on ? ' class="on"' : '', '></i>';
                  }
              }
            ?>
          </div>
          <div class="lp-phone-code">VCH-2026-001</div>
          <div class="lp-phone-foot"><i data-lucide="check-circle-2"></i> Show at the desk</div>
        </div>
      </div>
      <div class="lp-float lp-float-a"><span class="lp-dot"></span><div><small>APPLICATION</small><b>Approved</b></div></div>
      <div class="lp-float lp-float-b"><i data-lucide="scan-line"></i><div><small>SCANNED BY STAFF</small><b>Claim recorded</b></div></div>
    </div>
  </section>

  <section class="lp-stats" aria-label="At a glance" data-reveal>
    <div><strong><?= sprintf('%02d', $programCount) ?></strong><span>Active <?= $programCount === 1 ? 'program' : 'programs' ?></span></div>
    <div><strong><?= $totalAward > 0 ? e(format_money($totalAward)) : '—' ?></strong><span>Total award value listed</span></div>
    <div><strong>3</strong><span>Roles: student, staff, approver</span></div>
    <div><strong>QR</strong><span>Scan-to-claim, no paper lists</span></div>
  </section>

  <section class="lp-section" id="how" aria-labelledby="how-title">
    <div class="lp-head" data-reveal>
      <span class="lp-kicker">How it works</span>
      <h2 id="how-title">From first click to claimed.</h2>
      <p>Four clear steps, the same flow every cycle.</p>
    </div>
    <ol class="lp-steps">
      <li data-reveal style="--reveal-delay: 0ms"><span class="lp-step-n">01</span><i data-lucide="user-plus"></i><h3>Register</h3><p>Sign in with Google, fill in your details, and upload your 2x2 photo and enrollment form.</p></li>
      <li data-reveal style="--reveal-delay: 90ms"><span class="lp-step-n">02</span><i data-lucide="clipboard-check"></i><h3>Get reviewed</h3><p>The SK office reviews your registration. Approvers sign off, and you see notes if anything is needed.</p></li>
      <li data-reveal style="--reveal-delay: 180ms"><span class="lp-step-n">03</span><i data-lucide="ticket"></i><h3>Receive a voucher</h3><p>When a distribution batch opens, a QR voucher tied to your scholar profile is issued to you.</p></li>
      <li data-reveal style="--reveal-delay: 270ms"><span class="lp-step-n">04</span><i data-lucide="scan-line"></i><h3>Claim by QR</h3><p>Show your QR at the desk. Staff scan it and the claim is recorded instantly. No paper lists.</p></li>
    </ol>
  </section>

  <section class="lp-section lp-roles" aria-labelledby="roles-title">
    <div class="lp-head" data-reveal>
      <span class="lp-kicker">Built for everyone involved</span>
      <h2 id="roles-title">One system, three views.</h2>
    </div>
    <div class="lp-role-grid">
      <article data-reveal style="--reveal-delay: 0ms">
        <span class="lp-ico"><i data-lucide="graduation-cap"></i></span>
        <h3>Students</h3>
        <ul>
          <li>Register and upload documents once</li>
          <li>See your status and SK office notes</li>
          <li>Show profile and voucher QR from your phone</li>
        </ul>
      </article>
      <article data-reveal style="--reveal-delay: 90ms">
        <span class="lp-ico"><i data-lucide="scan-line"></i></span>
        <h3>SK office staff</h3>
        <ul>
          <li>Scan voucher QR codes at the desk</li>
          <li>Verify a scholar's profile before releasing aid</li>
          <li>Duplicate or invalid claims are rejected</li>
        </ul>
      </article>
      <article data-reveal style="--reveal-delay: 180ms">
        <span class="lp-ico"><i data-lucide="layout-dashboard"></i></span>
        <h3>Admins &amp; approvers</h3>
        <ul>
          <li>Manage programs, budgets, and batches</li>
          <li>Approve requests with a clear review trail</li>
          <li>Live dashboards, reports, and exports</li>
        </ul>
      </article>
    </div>
  </section>

  <section class="lp-section" id="features" aria-labelledby="features-title">
    <div class="lp-head" data-reveal>
      <span class="lp-kicker">Features</span>
      <h2 id="features-title">Everything the distribution day needs.</h2>
    </div>
    <div class="lp-bento">
      <article class="lp-bento-wide" data-reveal>
        <span class="lp-ico"><i data-lucide="qr-code"></i></span>
        <h3>Secure QR vouchers</h3>
        <p>Every voucher and scholar profile carries its own QR code. Scanning confirms identity and records the claim in one step.</p>
        <div class="lp-chips"><span>Voucher QR</span><span>Profile QR</span><span>Single claim</span></div>
      </article>
      <article data-reveal style="--reveal-delay: 80ms">
        <span class="lp-ico"><i data-lucide="activity"></i></span>
        <h3>Real-time tracking</h3>
        <p>Claimed vs. pending counts update the moment staff scan.</p>
      </article>
      <article data-reveal style="--reveal-delay: 120ms">
        <span class="lp-ico"><i data-lucide="git-pull-request-arrow"></i></span>
        <h3>Approval workflow</h3>
        <p>Changes go through approvers so important decisions have a second set of eyes.</p>
      </article>
      <article data-reveal style="--reveal-delay: 160ms">
        <span class="lp-ico"><i data-lucide="scroll-text"></i></span>
        <h3>Audit trail</h3>
        <p>Logins, claims, and edits are logged so every action can be traced.</p>
      </article>
      <article data-reveal style="--reveal-delay: 200ms">
        <span class="lp-ico"><i data-lucide="file-bar-chart"></i></span>
        <h3>Reports &amp; exports</h3>
        <p>Distribution and budget reports ready for the SK office and school records.</p>
      </article>
      <article class="lp-bento-wide" data-reveal style="--reveal-delay: 120ms">
        <span class="lp-ico"><i data-lucide="lock-keyhole"></i></span>
        <h3>Private by design</h3>
        <p>Sign-in uses Google. Uploaded IDs and forms are stored in private storage and are only shown to authorized staff, never as public links.</p>
        <div class="lp-chips"><span>Google sign-in</span><span>Private documents</span><span>Role-based access</span></div>
      </article>
    </div>
  </section>

  <section class="lp-section lp-sk" id="sk" aria-labelledby="sk-title">
    <div class="lp-sk-card" data-reveal>
      <div class="lp-sk-logo">
        <?php if ($skLogoUrl !== ''): ?>
          <img src="<?= e($skLogoUrl) ?>" alt="Sangguniang Kabataan of Barangay Cabay logo" loading="lazy">
        <?php else: ?>
          <span class="lp-sk-fallback" role="img" aria-label="Sangguniang Kabataan"><i data-lucide="users-round"></i><b>SK</b></span>
        <?php endif; ?>
      </div>
      <div class="lp-sk-copy">
        <span class="lp-kicker">Our partner &amp; scholarship provider</span>
        <h2 id="sk-title">Sangguniang Kabataan of Barangay Cabay</h2>
        <p>Scholarly was proposed for the SK of Barangay Cabay, Tiaong, Quezon. The SK sets the programs, decides who receives support, and gives scholarship assistance to students of the barangay. This system helps them run it with less paperwork and more transparency.</p>
        <ul class="lp-sk-points">
          <li><i data-lucide="hand-coins"></i><div><b>Gives the assistance</b><span>Funds and releases scholarship aid to Cabay students.</span></div></li>
          <li><i data-lucide="clipboard-list"></i><div><b>Runs the programs</b><span>Opens cycles, reviews registrations, and approves scholars.</span></div></li>
          <li><i data-lucide="scale"></i><div><b>Makes the decisions</b><span>Eligibility, selection, and funding always rest with the SK.</span></div></li>
        </ul>
      </div>
    </div>
  </section>

  <section class="lp-section" id="programs" aria-labelledby="programs-title">
    <div class="lp-head lp-head-row" data-reveal>
      <div>
        <span class="lp-kicker">Current opportunities</span>
        <h2 id="programs-title">Find your next step.</h2>
      </div>
      <p><strong><?= sprintf('%02d', $programCount) ?></strong> <?= $programCount === 1 ? 'program' : 'programs' ?> listed for Cabay students</p>
    </div>

    <?php if ($programs === []): ?>
      <div class="lp-empty" data-reveal>
        <i data-lucide="calendar-clock"></i>
        <h3>No active listings right now</h3>
        <p>New scholarship cycles are posted here when the SK office opens them. Register now so you are ready, or check back soon.</p>
        <a class="lp-btn lp-btn-primary lp-btn-sm" href="<?= e($register) ?>"><?= e($registerLabel) ?></a>
      </div>
    <?php else: ?>
      <div class="lp-programs">
        <?php foreach ($programs as $index => $program): ?>
          <?php
            $description = trim((string) ($program['description'] ?? ''));
            if ($description === '' || in_array(strtolower($description), ['test', 'todo', 'placeholder'], true)) {
              $description = 'Scholarship assistance from the Sangguniang Kabataan of Barangay Cabay. Contact the SK office for current eligibility and dates.';
            }
          ?>
          <article data-reveal style="--reveal-delay: <?= min($index * 80, 320) ?>ms">
            <div class="lp-program-top"><span class="lp-tag"><?= e($program['semester']) ?> · <?= e($program['academic_year']) ?></span><span class="lp-live"><i></i> Active</span></div>
            <h3><?= e($program['name']) ?></h3>
            <p><?= e($description) ?></p>
            <div class="lp-award"><small>Award per scholar</small><strong><?= e(format_money((float) $program['amount'])) ?></strong></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p class="lp-note"><i data-lucide="info"></i> Listings are for guidance. Eligibility, schedules, selection, and final funding are confirmed by the SK office.</p>
  </section>

  <section class="lp-section lp-app" id="app" aria-labelledby="app-title">
    <div class="lp-app-visual" aria-hidden="true" data-reveal>
      <div class="lp-phone lp-phone-sm">
        <div class="lp-phone-notch"></div>
        <div class="lp-phone-screen">
          <div class="lp-phone-head"><span>My application</span></div>
          <ul class="lp-timeline">
            <li class="done"><i></i>Registered</li>
            <li class="done"><i></i>Documents received</li>
            <li class="done"><i></i>Approved by SK office</li>
            <li class="now"><i></i>Voucher issued</li>
            <li><i></i>Claimed</li>
          </ul>
        </div>
      </div>
    </div>
    <div class="lp-app-copy" data-reveal>
      <span class="lp-kicker">Free Android app</span>
      <h2 id="app-title">Skip the line stress. Carry your scholarship in your pocket.</h2>
      <p>On distribution day, your QR is all you need. Open the app, show it at the desk, and you're done. No paperwork to dig up, no guessing about your status.</p>
      <ul class="lp-checks">
        <li><i data-lucide="check"></i> Live application status, updated the moment the SK office acts</li>
        <li><i data-lucide="check"></i> Your profile and voucher QR, ready even with a weak signal at the venue</li>
        <li><i data-lucide="check"></i> Full claim history, so you always know what you've received</li>
      </ul>
      <?php if ($mobileDownloadUrl !== ''): ?>
        <div class="lp-dl">
          <a class="lp-btn lp-btn-primary lp-btn-lg" href="<?= e($mobileDownloadUrl) ?>" target="_blank" rel="noopener noreferrer" data-lp-download>
            <i data-lucide="download"></i> Download for Android
          </a>
          <ul class="lp-dl-meta">
            <li><i data-lucide="badge-check"></i> Free</li>
            <li><i data-lucide="shield-check"></i> Official SK Cabay app</li>
            <li><i data-lucide="smartphone"></i> Android 6.0+</li>
          </ul>
        </div>
        <ol class="lp-dl-steps">
          <li><b>1</b> Tap download and open the file</li>
          <li><b>2</b> Allow “install from this source” if Android asks</li>
          <li><b>3</b> Sign in with the same Google account</li>
        </ol>
      <?php else: ?>
        <span class="lp-soon"><i data-lucide="clock-3"></i> Android download coming soon</span>
      <?php endif; ?>
    </div>
  </section>

  <section class="lp-section" id="faq" aria-labelledby="faq-title">
    <div class="lp-head" data-reveal>
      <span class="lp-kicker">FAQ</span>
      <h2 id="faq-title">Good to know.</h2>
    </div>
    <div class="lp-faq" data-reveal>
      <details>
        <summary>Who can apply?</summary>
        <p>Eligibility is set by the SK office for each program. Register first, then the SK office confirms whether you qualify for a listed program.</p>
      </details>
      <details>
        <summary>What do I need to register?</summary>
        <p>A Google account, your personal and school details, a 2x2 photo, and your enrollment form.</p>
      </details>
      <details>
        <summary>How will I know my status?</summary>
        <p>Sign in on the web or the Android app to see your current status and any notes from the SK office. If more information is needed, you can update and resubmit.</p>
      </details>
      <details>
        <summary>How do I claim my assistance?</summary>
        <p>When a distribution batch opens and your voucher is issued, show the QR from the app at the distribution desk. Staff will scan it to record your claim.</p>
      </details>
      <details>
        <summary>Is my information safe?</summary>
        <p>Documents are kept in private storage and are only available to authorized staff. Actions in the system are recorded in an audit log.</p>
      </details>
      <details>
        <summary>Who decides who receives funding?</summary>
        <p>The SK office. Scholarly is a tool that supports their process; it does not decide selection or funding.</p>
      </details>
    </div>
  </section>

  <section class="lp-section lp-team" id="team" aria-labelledby="team-title">
    <div class="lp-head" data-reveal>
      <span class="lp-kicker">The people behind Scholarly</span>
      <h2 id="team-title">A local project. A student team.</h2>
      <p>Scholarly is a 4th-year capstone at SLSU Tiaong, built around a real need in Barangay Cabay.</p>
    </div>
    <div class="lp-team-grid">
      <?php foreach ([
        ['neil-garcia.jpg', 'Neil Jr. P. Garcia', 'Garcia, Neil Jr. P.'],
        ['kier-dolor.jpg', 'Kier Dolor', 'Dolor, Kier'],
        ['lray-aquino.jpg', 'LRay Aquino', 'Aquino, LRay'],
      ] as $i => [$file, $alt, $name]): ?>
        <article data-reveal style="--reveal-delay: <?= $i * 90 ?>ms">
          <img src="<?= e(base_url('assets/images/team/' . $file)) ?>" alt="<?= e($alt) ?>" loading="lazy">
          <h3><?= e($name) ?></h3>
          <span>SLSU Tiaong · 4th Year</span>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="lp-cta" data-reveal>
    <h2>Ready when the next cycle opens.</h2>
    <p>Register once and be set for every distribution.</p>
    <div class="lp-actions lp-actions-center">
      <a class="lp-btn lp-btn-primary" href="<?= e($register) ?>"><?= e($registerLabel) ?> <i data-lucide="arrow-up-right"></i></a>
      <?php if ($mobileDownloadUrl !== ''): ?>
        <a class="lp-btn lp-btn-ghost" href="<?= e($mobileDownloadUrl) ?>" target="_blank" rel="noopener noreferrer" data-lp-download><i data-lucide="download"></i> Download the app</a>
      <?php endif; ?>
      <a class="lp-btn lp-btn-ghost" href="<?= e(base_url('login.php')) ?>">Staff portal</a>
    </div>
  </section>

  <?php if ($mobileDownloadUrl !== ''): ?>
    <a class="lp-sticky-dl" href="<?= e($mobileDownloadUrl) ?>" target="_blank" rel="noopener noreferrer" data-lp-download>
      <i data-lucide="download"></i> Get the Android app
    </a>
  <?php endif; ?>
</main>

<footer class="lp-footer">
  <div class="lp-footer-brand">
    <a class="lp-brand" href="<?= e(base_url('index.php')) ?>">
      <img src="<?= e(base_url('assets/images/slsu-tiaong-logo.png')) ?>" alt="" width="36" height="36">
      <?php if ($skLogoUrl !== ''): ?><img src="<?= e($skLogoUrl) ?>" alt="" width="36" height="36"><?php endif; ?>
      <span>Scholarly<b>.</b><small>SLSU Tiaong · Brgy. Cabay</small></span>
    </a>
    <p>A student-led capstone prototype for the Sangguniang Kabataan of Barangay Cabay, Tiaong, Quezon.</p>
  </div>
  <nav aria-label="Footer">
    <a href="#how">How it works</a>
    <a href="#features">Features</a>
    <a href="#programs">Programs</a>
    <a href="#faq">FAQ</a>
    <a href="<?= e(base_url('login.php')) ?>">Staff sign in</a>
  </nav>
  <small>© <?= date('Y') ?> Scholarly · SLSU Tiaong capstone project. Eligibility and funding decisions remain with the SK office.</small>
</footer>
</div>
