(function () {
  var dialog = document.getElementById('close-batch-dialog');
  if (!dialog) return;
  var check = dialog.querySelector('[data-close-batch-check]');
  var submit = dialog.querySelector('[data-close-batch-submit]');

  document.querySelectorAll('[data-close-batch-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      check.checked = false;
      submit.disabled = true;
      dialog.showModal();
    });
  });
  dialog.querySelectorAll('[data-close-batch-cancel]').forEach(function (btn) {
    btn.addEventListener('click', function () { dialog.close(); });
  });
  check.addEventListener('change', function () { submit.disabled = !check.checked; });
  dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
})();
