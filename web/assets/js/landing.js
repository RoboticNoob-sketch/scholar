document.addEventListener('DOMContentLoaded', () => {
  const nav = document.querySelector('[data-lp-nav]');
  const toggle = document.querySelector('[data-lp-toggle]');
  const menu = document.getElementById('lp-menu');
  if (!nav || !toggle || !menu) return;

  const setOpen = (open) => {
    menu.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
  };

  toggle.addEventListener('click', () => setOpen(!menu.classList.contains('is-open')));
  menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));

  const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 8);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
});
