/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#menuitemslist').sortable();
  for (const element of document.querySelectorAll('#menuitemslist tr td input.position')) {
    element.style.display = 'none';
  }
  for (const element of document.querySelectorAll('#menuitemslist tr td.handle')) {
    element.classList.add('handler');
  }

  document.getElementById('menuitems')?.addEventListener('submit', () => {
    const order = [];
    for (const element of document.querySelectorAll('#menuitemslist tr td input.position')) {
      order.push(element.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    }
    document.querySelector('input[name=im_order]').value = order.join(',');
    return true;
  });

  for (const element of document.querySelectorAll('.checkboxes-helpers')) {
    dotclear.checkboxesHelpers(
      element,
      $('#menuitems td input[name^=items_selected]'),
      '#menuitems td input[name^=items_selected]',
      '#menuitems #remove-action',
    );
  }
  dotclear.condSubmit('#menuitems tr td input[name^=items_selected]', '#menuitems #remove-action');
  dotclear.responsiveCellHeaders(document.querySelector('#menuitems table'), '#menuitems table', 2);

  const msg = dotclear.getData('simplemenu');
  document.querySelector('#menuitems #remove-action')?.addEventListener('click', (event) => {
    if (window.confirm(msg.confirm_items_delete)) return true;
    event.preventDefault();
    return false;
  });
});
