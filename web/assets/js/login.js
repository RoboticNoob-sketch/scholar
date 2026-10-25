(function () {
  var toggle = document.querySelector('[data-pw-toggle]');
  var input = document.querySelector('[data-pw]');
  if (!toggle || !input) return;
  toggle.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    toggle.innerHTML = '<i data-lucide="' + (show ? 'eye-off' : 'eye') + '"></i>';
    if (window.lucide) window.lucide.createIcons();
  });
})();
