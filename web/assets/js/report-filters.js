(function () {
  var form = document.getElementById('report-filters');
  if (!form) return;

  var period = form.querySelector('[name="period"]');
  var from = form.querySelector('[name="date_from"]');
  var to = form.querySelector('[name="date_to"]');
  var program = form.querySelector('[name="program_id"]');
  var batch = form.querySelector('[name="batch_id"]');

  // Summary cards and charts are server-rendered, so changing a filter reloads the page.
  function submit() {
    if (period.value !== 'custom' && period.value !== '') {
      from.value = '';
      to.value = '';
    }
    form.submit();
  }

  [from, to].forEach(function (input) {
    input.addEventListener('change', function () {
      period.value = from.value || to.value ? 'custom' : '';
    });
  });

  // A batch belongs to one program, so hide batches outside the selected program.
  function syncBatches() {
    var id = program.value;
    Array.prototype.forEach.call(batch.options, function (opt) {
      var owner = opt.getAttribute('data-program');
      opt.hidden = !!owner && id !== '0' && owner !== id;
    });
  }
  syncBatches();

  form.addEventListener('change', function (event) {
    event.stopImmediatePropagation();
    if (event.target === program) {
      batch.value = '0';
    }
    submit();
  }, true);
})();
