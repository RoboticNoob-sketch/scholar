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

(function () {
  var bar = document.querySelector('.lp-sticky-dl');
  var app = document.getElementById('app');
  var hero = document.querySelector('.lp-hero');
  if (!bar || !app || !hero || !('IntersectionObserver' in window)) return;
  var pastHero = false, inApp = false;
  function update() { bar.classList.toggle('is-visible', pastHero && !inApp); }
  new IntersectionObserver(function (e) { pastHero = !e[0].isIntersecting; update(); }).observe(hero);
  new IntersectionObserver(function (e) { inApp = e[0].isIntersecting; update(); }).observe(app);
})();
