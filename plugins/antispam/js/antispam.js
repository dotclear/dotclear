/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('antispam'));

dotclear.ready(() => {
  // DOM ready and content loaded

  const $filters_list = $('#filters-list');
  if (!$filters_list.length) {
    return;
  }
  $filters_list.sortable();
  document.querySelectorAll('#filters-list tr td.handle').forEach((element) => element.classList.add('handler'));

  document.querySelectorAll('#filters-list tr td input.position').forEach((element) => {
    element.style.display = 'none';
  });

  document.getElementById('filters-list-form')?.addEventListener('submit', () => {
    const order = [];
    document.querySelectorAll('#filters-list tr td input.position').forEach((element) => {
      order.push(element.name.replace(/^f_order\[([^\]]+)\]$/, '$1'));
    });
    document.querySelector('input[name=filters_order]').value = order.join(',');
    return true;
  });

  document
    .querySelector('form input[type=submit][name=delete_all]')
    ?.addEventListener('click', () => window.confirm(dotclear.msg.confirm_spam_delete));

  // Prepare mobile display for tables
  dotclear.responsiveCellHeaders(document.querySelector('#filters-list-form table'), '#filters-list-form table', 1, true);
});
