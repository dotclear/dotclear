/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#links-list').sortable();
  $('#links-form').on('submit', () => {
    const order = [];
    $('#links-list tr td input.position').each(function () {
      order.push(this.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    });
    $('input[name=links_order]')[0].value = order.join(',');
    return true;
  });
  $('#links-list tr td input.position').hide();
  $('#links-list tr td.handle').addClass('handler');
  dotclear.condSubmit('#links-form td input[type="checkbox"]', '#links-form #remove-action');
});
