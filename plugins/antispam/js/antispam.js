/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const $filters_list = $('#filters-list');
  if (!$filters_list.length) {
    return;
  }

  $filters_list.sortable();
  for (const element of document.querySelectorAll('#filters-list tr td input.position')) {
    element.style.display = 'none';
  }
  for (const element of document.querySelectorAll('#filters-list tr td.handle')) {
    element.classList.add('handler');
  }

  document.getElementById('filters-list-form')?.addEventListener('submit', () => {
    const order = [];
    for (const element of document.querySelectorAll('#filters-list tr td input.position')) {
      order.push(element.name.replace(/^f_order\[([^\]]+)\]$/, '$1'));
    }
    document.querySelector('input[name=filters_order]').value = order.join(',');
    return true;
  });

  const msg = dotclear.getData('antispam');
  document.querySelector('form input[type=submit][name=delete_all]')?.addEventListener('click', (event) => {
    if (window.confirm(msg.confirm_spam_delete)) return true;
    event.preventDefault();
    return false;
  });

  // Prepare mobile display for tables
  dotclear.responsiveCellHeaders(document.querySelector('#filters-list-form table'), '#filters-list-form table', 1, true);
});
