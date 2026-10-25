(function () {
  var form = document.getElementById('audit-filters');
  if (!form) return;

  var period = form.querySelector('[data-audit-period]');
  var from = form.querySelector('[name="date_from"]');
  var to = form.querySelector('[name="date_to"]');

  function iso(date) {
    var local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
  }

  function notify(field) {
    field.dispatchEvent(new Event('change', { bubbles: true }));
  }

  var applying = false;

  period.addEventListener('change', function () {
    var value = period.value;
    if (value === 'custom') return;
    var today = new Date();
    to.value = '';
    if (value === 'all') {
      from.value = '';
    } else if (value === 'today') {
      from.value = iso(today);
    } else {
      var start = new Date();
      start.setDate(today.getDate() - (parseInt(value, 10) - 1));
      from.value = iso(start);
    }
    applying = true;
    notify(from);
    applying = false;
  });

  form.querySelectorAll('[data-audit-date]').forEach(function (input) {
    input.addEventListener('change', function () {
      if (!applying) period.value = 'custom';
    });
  });

  form.querySelector('[data-audit-reset]').addEventListener('click', function () {
    form.reset();
    period.value = '7';
    period.dispatchEvent(new Event('change'));
  });
})();
