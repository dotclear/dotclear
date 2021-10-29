/*global $, dotclear */
'use strict';

$(() => {
  $('#menuitemslist').sortable({
    cursor: 'move',
  });
  $('#menuitemslist tr')
    .on('mouseenter', function () {
      $(this).css({
        cursor: 'move',
      });
    })
    .on('mouseleave', function () {
      $(this).css({
        cursor: 'auto',
      });
    });
  $('#menuitems').on('submit', () => {
    let order = [];
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
