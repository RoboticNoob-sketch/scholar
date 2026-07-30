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

  document.querySelectorAll('.datatable-server').forEach((table) => {
    const $table = jQuery(table);
    const ajaxUrl = table.dataset.ajaxUrl;
    const filterFormSelector = table.dataset.filterForm || '';
    const defaultOrder = JSON.parse(table.dataset.defaultOrder || '[[0, "desc"]]');
    const checkboxColumn = table.dataset.checkboxColumn;

    const dt = $table.DataTable({
      processing: true,
      serverSide: true,
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
      order: defaultOrder,
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      searching: true,
      language: {
        search: 'Search table:',
        lengthMenu: 'Show _MENU_ rows',
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
