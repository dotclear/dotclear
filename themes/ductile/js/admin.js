/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#stickerslist').sortable();
  $('#theme_config').on('submit', () => {
    const order = [];
    $('#stickerslist tr td input.position').each(function () {
      order.push(this.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    });
    $('input[name=ds_order]')[0].value = order.join(',');
    return true;
  });
  $('#stickerslist tr td input.position').hide();
  $('#stickerslist tr td.handle').addClass('handler');
});
