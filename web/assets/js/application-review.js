(function () {
  // Notes are shown to the student in the app, so templates are written for them.
  var templates = {
    under_review: {
      hint: 'Optional',
      text: 'Your registration is now being reviewed by the scholarship office. We will update you here once a decision is made.'
    },
    needs_info: {
      hint: 'Required: tell the student what to fix',
      text: 'Please re-upload a clear copy of your [2x2 photo / enrollment form]. The file we received could not be read. Update your registration in the app once ready.'
    },
    approved: {
      hint: 'Optional',
      text: 'Congratulations! Your registration has been approved and your scholar profile is being set up. Program assignment and funding are confirmed separately.'
    },
    declined: {
      hint: 'Recommended: explain the reason',
      text: 'We are unable to approve your registration at this time because [reason]. You may contact the scholarship office if you have questions.'
    }
  };

  function init(form) {
    var select = form.querySelector('select[name="status"]');
    var field = form.querySelector('[data-student-no-field]');
    var input = field && field.querySelector('input');
    var note = form.querySelector('[data-review-note]');
    var hint = form.querySelector('[data-note-hint]');
    if (!select || !field || !note) return;

    var lastTemplate = null;

    function sync(fillNote) {
      var status = select.value;
      var approving = status === 'approved';
      field.hidden = !approving;
      if (input) {
        input.required = approving;
        if (!approving) input.value = '';
      }

      var tpl = templates[status];
      if (!tpl) return;
      if (hint) hint.textContent = tpl.hint;
      note.placeholder = tpl.text;
      note.required = status === 'needs_info';

      // Replace only empty text or a previous suggestion, never something a person typed or saved.
      if (fillNote) {
        var current = note.value.trim();
        if (current === '' || current === lastTemplate) {
          note.value = tpl.text;
          lastTemplate = tpl.text;
        }
      }
    }

    select.addEventListener('change', function () {
      sync(true);
      markQuick();
    });

    var quick = form.querySelectorAll('[data-quick-decision]');
    function markQuick() {
      quick.forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-quick-decision') === select.value); });
    }
    quick.forEach(function (b) {
      b.addEventListener('click', function () {
        select.value = b.getAttribute('data-quick-decision');
        select.dispatchEvent(new Event('change'));
        var target = select.value === 'approved' ? input : note;
        if (target) target.focus();
      });
    });
    markQuick();
    sync(false);
  }

  document.querySelectorAll('.application-review-form').forEach(init);

  // Review opens as a dialog: one open at a time, closes with X, backdrop click or Escape.
  var rows = document.querySelectorAll('details.application-row-review');
  function closeAll() { rows.forEach(function (d) { d.removeAttribute('open'); }); document.body.classList.remove('review-open'); }
  rows.forEach(function (d) {
    d.addEventListener('toggle', function () {
      if (d.open) {
        rows.forEach(function (o) { if (o !== d) o.removeAttribute('open'); });
        document.body.classList.add('review-open');
      } else if (!document.querySelector('details.application-row-review[open]')) {
        document.body.classList.remove('review-open');
      }
    });
    d.addEventListener('click', function (e) {
      if (e.target.closest('[data-review-close]') || e.target === d) closeAll();
    });
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
})();
