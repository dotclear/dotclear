/*global jQuery, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const filters_list = jQuery('#filters-list');
  if (!filters_list.length) {
    return;
  }

  filters_list.sortable();
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
});
