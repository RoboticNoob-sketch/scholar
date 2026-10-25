document.addEventListener('DOMContentLoaded', () => {
  document.documentElement.classList.add('js');

  if (window.lucide) {
    lucide.createIcons();
  }

  const revealItems = Array.from(document.querySelectorAll('[data-reveal]'));
  if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -36px 0px' });
    revealItems.forEach((item) => revealObserver.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add('is-visible'));
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

  document.querySelectorAll('[data-program-scholar-picker]').forEach((picker) => {
    const search = picker.querySelector('[data-scholar-search]');
    const rows = Array.from(picker.querySelectorAll('[data-scholar-row]'));
    const selectedLabel = picker.querySelector('[data-scholar-selected]');
    const visibleLabel = picker.querySelector('[data-scholar-visible]');
    const emptyState = picker.querySelector('[data-scholar-empty]');
    const filterButtons = Array.from(picker.querySelectorAll('[data-scholar-filter]'));
    let filterMode = 'all';

    picker.querySelectorAll('.scholar-picker-avatar img').forEach((image) => {
      image.addEventListener('error', () => image.remove(), { once: true });
    });

    const updatePicker = () => {
      const query = (search?.value || '').trim().toLocaleLowerCase();
      let visible = 0;
      let selected = 0;
      let unselected = 0;

      rows.forEach((row) => {
        const checkbox = row.querySelector('input[type="checkbox"]');
        const isSelected = checkbox?.checked ?? false;
        const matchesQuery = row.textContent.toLocaleLowerCase().includes(query);
        const matchesMode = filterMode === 'all'
          || (filterMode === 'selected' && isSelected)
          || (filterMode === 'unselected' && !isSelected);
        row.hidden = !matchesQuery || !matchesMode;
        row.classList.toggle('is-selected', isSelected);
        if (isSelected) selected++;
        else unselected++;
        if (!row.hidden) visible++;
      });

      if (selectedLabel) selectedLabel.textContent = `${selected} selected`;
      if (visibleLabel) visibleLabel.textContent = `${visible} shown`;
      picker.querySelector('[data-filter-count="all"]').textContent = String(rows.length);
      picker.querySelector('[data-filter-count="selected"]').textContent = String(selected);
      picker.querySelector('[data-filter-count="unselected"]').textContent = String(unselected);
      if (emptyState) emptyState.hidden = visible !== 0;
    };

    search?.addEventListener('input', updatePicker);
    filterButtons.forEach((button) => {
      button.addEventListener('click', () => {
        filterMode = button.dataset.scholarFilter || 'all';
        filterButtons.forEach((filterButton) => {
          const isActive = filterButton === button;
          filterButton.classList.toggle('is-active', isActive);
          filterButton.setAttribute('aria-pressed', String(isActive));
        });
        updatePicker();
      });
    });
    picker.addEventListener('change', (event) => {
      if (event.target.matches('.program-scholar-option')) updatePicker();
    });
    picker.querySelector('[data-scholar-select-visible]')?.addEventListener('click', () => {
      rows.forEach((row) => {
        if (!row.hidden) {
          const checkbox = row.querySelector('input[type="checkbox"]');
          if (checkbox) checkbox.checked = true;
        }
      });
      updatePicker();
    });
    picker.querySelector('[data-scholar-clear]')?.addEventListener('click', () => {
      rows.forEach((row) => {
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = false;
      });
      updatePicker();
    });

    updatePicker();
  });

  const batchBudgetForm = document.querySelector('[data-batch-budget-form]');
  if (batchBudgetForm) {
    const programSelect = batchBudgetForm.querySelector('[name="program_id"]');
    const generateCheckbox = batchBudgetForm.querySelector('[name="generate_vouchers"]');
    const status = batchBudgetForm.querySelector('[data-batch-budget-status]');
    const submitBatch = batchBudgetForm.querySelector('[data-submit-batch]');
    const submitPrint = batchBudgetForm.querySelector('[data-submit-print]');

    const updateBatchBudget = () => {
      const option = programSelect?.selectedOptions[0];
      const budget = Number(option?.dataset.budget || 0);
      const committed = Number(option?.dataset.committed || 0);
      const award = Number(option?.dataset.award || 0);
      const enrolled = Number(option?.dataset.enrolled || 0);
      const available = budget - committed;
      const needsVouchers = Boolean(generateCheckbox?.checked);
      const voucherEstimate = award * enrolled;
      const hasBudget = budget > 0;
      const hasRemaining = available > 0;
      const canIssueVouchers = voucherEstimate <= available;
      const money = (value) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);

      if (status) {
        status.classList.toggle('is-blocked', !hasBudget || !hasRemaining || (needsVouchers && !canIssueVouchers));
        if (!hasBudget) {
          status.textContent = 'This program has no total budget. Add a budget before requesting a batch.';
        } else if (!hasRemaining) {
          status.textContent = 'No budget remains for this program. Increase its budget or release existing commitments.';
        } else if (!canIssueVouchers) {
          status.textContent = `Insufficient balance: this batch needs about ${money(voucherEstimate)} for all enrolled scholars, but only ${money(available)} is available.`;
        } else if (needsVouchers) {
          status.textContent = `${money(available)} available · estimated voucher commitment ${money(voucherEstimate)} for ${enrolled} enrolled scholars.`;
        } else {
          status.textContent = `${money(available)} available · this batch may need ${money(voucherEstimate)} for all enrolled scholars.`;
        }
      }

      if (submitBatch) submitBatch.disabled = !hasBudget || !hasRemaining || !canIssueVouchers;
      if (submitPrint) submitPrint.disabled = !hasBudget || !hasRemaining || !canIssueVouchers;
    };

    programSelect?.addEventListener('change', updateBatchBudget);
    generateCheckbox?.addEventListener('change', updateBatchBudget);
    updateBatchBudget();
  }

  const voucherDialog = document.querySelector('#voucher-preview-dialog');
  const voucherQr = document.querySelector('#voucher-qr');
  const voucherCode = document.querySelector('#voucher-preview-code');
  const voucherPayload = document.querySelector('#voucher-preview-payload');

  document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('.voucher-preview-trigger');
    if (trigger && voucherDialog && voucherQr && voucherCode && voucherPayload) {
      const code = trigger.dataset.voucherCode || '';
      const payload = `VCH|${code}`;
      voucherQr.replaceChildren();
      voucherCode.textContent = code;
      voucherPayload.textContent = payload;
      document.querySelector('#voucher-preview-name').textContent = trigger.dataset.voucherName || '';
      document.querySelector('#voucher-preview-student').textContent = trigger.dataset.voucherStudentNo || '';
      document.querySelector('#voucher-preview-amount').textContent = trigger.dataset.voucherAmount || '';

      if (window.QRCode) {
        new QRCode(voucherQr, {
          text: payload,
          width: 196,
          height: 196,
          colorDark: '#102017',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M,
        });
      } else {
        voucherQr.textContent = 'QR preview unavailable. Copy the voucher code instead.';
      }
      voucherDialog.showModal();
      return;
    }

    if (event.target.closest('[data-voucher-close]')) {
      voucherDialog?.close();
      return;
    }

    const copyButton = event.target.closest('[data-voucher-copy]');
    if (copyButton && voucherCode) {
      try {
        await navigator.clipboard.writeText(voucherCode.textContent || '');
        copyButton.innerHTML = '<i data-lucide="check"></i> Copied';
        window.lucide?.createIcons();
      } catch {
        copyButton.textContent = 'Copy unavailable';
      }
    }
  });

  voucherDialog?.addEventListener('click', (event) => {
    if (event.target === voucherDialog) {
      voucherDialog.close();
    }
  });
});
