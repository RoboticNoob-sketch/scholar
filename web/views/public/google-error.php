<main class="public-message-page">
  <div class="public-message-mark public-message-mark-error"><i data-lucide="circle-alert"></i></div>
  <div class="public-section-kicker">SIGN-IN NOT COMPLETED</div>
  <h1>We could not sign you in.</h1>
  <p><?= e($message) ?></p>
  <a class="public-button-primary" href="<?= e(base_url('auth/google-start.php')) ?>">Try Google sign-in again <i data-lucide="arrow-up-right"></i></a>
</main>