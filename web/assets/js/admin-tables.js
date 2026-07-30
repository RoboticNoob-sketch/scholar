document.addEventListener('DOMContentLoaded', () => {
  if (!window.jQuery || !jQuery.fn.dataTable) {
    return;
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
          if (filterFormSelector) {
            const form = document.querySelector(filterFormSelector);
            if (form) {
              new FormData(form).forEach((value, key) => {
                if (value !== '') {
                  d[key] = value;
                }
              });
            }
          }
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
      const form = document.querySelector(filterFormSelector);
      form?.querySelectorAll('select, input').forEach((input) => {
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
