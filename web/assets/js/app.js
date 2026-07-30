document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) {
    lucide.createIcons();
  }

  const shell = document.querySelector('.app-shell');
  const toggle = document.querySelector('[data-sidebar-toggle]');
  const overlay = document.querySelector('.sidebar-overlay');

  toggle?.addEventListener('click', () => {
    shell?.classList.toggle('sidebar-open');
  });

  overlay?.addEventListener('click', () => {
    shell?.classList.remove('sidebar-open');
  });

  document.querySelectorAll('.nav-item').forEach((item) => {
    item.addEventListener('click', () => {
      shell?.classList.remove('sidebar-open');
    });
  });
});
