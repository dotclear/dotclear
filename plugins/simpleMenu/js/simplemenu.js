/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#menuitemslist').sortable();
  document.querySelectorAll('#menuitemslist tr td.handle').forEach((element) => element.classList.add('handler'));

  document.querySelectorAll('#menuitemslist tr td input.position').forEach((element) => {
    element.style.display = 'none';
  });

  document.getElementById('menuitems')?.addEventListener('submit', () => {
    const order = [];
    document.querySelectorAll('#menuitemslist tr td input.position').forEach((element) => {
      order.push(element.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    });
    document.querySelector('input[name=im_order]').value = order.join(',');
    return true;
  });

  dotclear.condSubmit('#menuitems tr td input[name^=items_selected]', '#menuitems #remove-action');
  dotclear.responsiveCellHeaders(document.querySelector('#menuitems table'), '#menuitems table', 2);
});
