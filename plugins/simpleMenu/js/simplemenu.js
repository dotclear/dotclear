/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#menuitemslist').sortable();
  $('#menuitems').on('submit', () => {
    const order = [];
    $('#menuitemslist tr td input.position').each(function () {
      order.push(this.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    });
    $('input[name=im_order]')[0].value = order.join(',');
    return true;
  });
  $('#menuitemslist tr td input.position').hide();
  $('#menuitemslist tr td.handle').addClass('handler');
  dotclear.condSubmit('#menuitems tr td input[name^=items_selected]', '#menuitems #remove-action');
  dotclear.responsiveCellHeaders(document.querySelector('#menuitems table'), '#menuitems table', 2);
});
