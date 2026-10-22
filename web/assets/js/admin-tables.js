document.addEventListener('DOMContentLoaded', () => {
  if (!window.jQuery || !jQuery.fn.dataTable) {
    return;
  }

  function appendFilterParams(params, selector) {
    if (!selector) {
      return;
    }

    const container = document.querySelector(selector);
    if (!container) {
      return;
    }

    if (container instanceof HTMLFormElement) {
      new FormData(container).forEach((value, key) => {
        if (value !== '') {
          params[key] = value;
        }
      });
      return;
    }

    container.querySelectorAll('input, select, textarea').forEach((field) => {
      if (!field.name || field.disabled) {
        return;
      }
      if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
        return;
      }
      if (field.value !== '') {
        params[field.name] = field.value;
      }
    });
  }

  function enhanceGrid(table, dt) {
    const wrapper = dt.table().container();
    const toolbar = document.createElement('div');
    toolbar.className = 'grid-toolbar';
    toolbar.innerHTML = `
      <div class="grid-toolbar-start">
        <label class="grid-search">
          <i data-lucide="search"></i>
          <input type="search" aria-label="Search rows" placeholder="Search this table…">
        </label>
        <span class="grid-result-count" aria-live="polite"></span>
      </div>
      <div class="grid-toolbar-actions">
        <select class="grid-page-size" aria-label="Rows per page"></select>
        <details class="grid-columns">
          <summary title="Choose visible columns" aria-label="Choose visible columns"><i data-lucide="columns-3"></i></summary>
          <div class="grid-columns-menu"></div>
        </details>
        <button class="grid-tool-button" type="button" data-grid-density title="Toggle compact density" aria-label="Toggle compact density" aria-pressed="false"><i data-lucide="rows-3"></i></button>
        <button class="grid-tool-button" type="button" data-grid-export title="Export current page as CSV" aria-label="Export current page as CSV"><i data-lucide="download"></i></button>
      </div>`;
    wrapper.insertBefore(toolbar, wrapper.firstChild);

    const search = toolbar.querySelector('input[type="search"]');
    const count = toolbar.querySelector('.grid-result-count');
    const pageSize = toolbar.querySelector('.grid-page-size');
    const columnsMenu = toolbar.querySelector('.grid-columns-menu');
    const densityButton = toolbar.querySelector('[data-grid-density]');
    const exportButton = toolbar.querySelector('[data-grid-export]');
    const lengths = table.classList.contains('datatable-client') ? [5, 10, 25] : [10, 25, 50, 100];

    lengths.forEach((length) => {
      const option = document.createElement('option');
      option.value = String(length);
      option.textContent = `${length} rows`;
      pageSize.append(option);
    });
    pageSize.value = String(dt.page.len());
    pageSize.addEventListener('change', () => dt.page.len(Number(pageSize.value)).draw());

    dt.columns().every(function (index) {
      const header = this.header();
      const label = (header.textContent || `Column ${index + 1}`).trim();
      const option = document.createElement('label');
      option.className = 'grid-column-option';
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.checked = this.visible();
      checkbox.addEventListener('change', () => this.visible(checkbox.checked));
      const text = document.createElement('span');
      text.textContent = label;
      option.append(checkbox, text);
      columnsMenu.append(option);
    });

    let searchTimer;
    search.addEventListener('input', () => {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(() => dt.search(search.value).draw(), 180);
    });

    densityButton.addEventListener('click', () => {
      const compact = wrapper.dataset.density !== 'compact';
      wrapper.dataset.density = compact ? 'compact' : 'comfortable';
      densityButton.setAttribute('aria-pressed', String(compact));
    });

    exportButton.addEventListener('click', () => {
      const indexes = dt.columns(':visible').indexes().toArray();
      const headers = indexes.map((index) => dt.column(index).header().innerText.trim());
      const rows = dt.rows({ search: 'applied', page: 'current' }).nodes().toArray();
      const escapeCell = (value) => {
        const text = String(value).replace(/\s+/g, ' ').trim();
        const safeText = /^[=+\-@]/.test(text) ? `'${text}` : text;
        return `"${safeText.replace(/"/g, '""')}"`;
      };
      const csv = [headers, ...rows.map((row) => indexes.map((index) => row.cells[index]?.innerText ?? ''))]
        .map((row) => row.map(escapeCell).join(','))
        .join('\r\n');
      const url = URL.createObjectURL(new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' }));
      const link = document.createElement('a');
      link.href = url;
      link.download = `${table.dataset.exportName || table.id || 'table'}-page.csv`;
      link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    });

    dt.on('draw.dt search.dt order.dt', () => {
      const page = dt.page.info();
      count.textContent = page.recordsDisplay === page.recordsTotal
        ? `${page.recordsTotal} rows`
        : `${page.recordsDisplay} of ${page.recordsTotal} rows`;
    });

    if (window.lucide?.createIcons) {
      window.lucide.createIcons({ root: toolbar });
    }
  }

  document.querySelectorAll('.datatable-server, .datatable-client').forEach((table) => {
    const $table = jQuery(table);
    const ajaxUrl = table.dataset.ajaxUrl;
    const filterFormSelector = table.dataset.filterForm || '';
    const defaultOrder = JSON.parse(table.dataset.defaultOrder || '[[0, "desc"]]');
    const checkboxColumn = table.dataset.checkboxColumn;
    const serverSide = table.classList.contains('datatable-server');

    const dt = $table.DataTable({
      processing: serverSide,
      serverSide,
      ...(serverSide ? {
        ajax: {
          url: ajaxUrl,
          data(d) {
            appendFilterParams(d, filterFormSelector);
          },
          error(xhr) {
            let message = 'Could not load table data.';
            try {
              const payload = JSON.parse(xhr.responseText || '{}');
              if (payload.error) {
                message = payload.error;
              }
            } catch (err) {
              if (xhr.status === 401) {
                message = 'Session expired. Please sign in again.';
              }
            }
            window.alert(message);
          },
        },
      } : {}),
      order: defaultOrder,
      pageLength: Number(table.dataset.pageLength) || (serverSide ? 25 : 5),
      lengthMenu: serverSide ? [10, 25, 50, 100] : [5, 10, 25],
      searching: true,
      dom: 'rt<"grid-footer"ip>',
      language: {
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        infoEmpty: 'No entries to show',
        zeroRecords: 'No matching records found',
        processing: 'Loading…',
      },
      columnDefs: [
        { targets: '_all', orderSequence: ['asc', 'desc'] },
        ...(checkboxColumn !== undefined
          ? [{ targets: Number(checkboxColumn), orderable: false, searchable: false }]
          : []),
      ],
    });

    enhanceGrid(table, dt);

    if (filterFormSelector) {
      const container = document.querySelector(filterFormSelector);
      container?.querySelectorAll('select, input, textarea').forEach((input) => {
        input.addEventListener('change', () => {
          dt.ajax.reload();
        });
      });
    }
  });

  document.querySelectorAll('[data-reload-page="1"]').forEach((input) => {
    input.addEventListener('change', () => {
      const params = new URLSearchParams(window.location.search);
      params.set(input.name, input.value);
      window.location.search = params.toString();
    });
  });
});
