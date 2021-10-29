/*global $, dotclear */
'use strict';

$(() => {
  $('#links-list').sortable({
    cursor: 'move',
  });
  $('#links-list tr')
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
  $('#links-form').on('submit', () => {
    let order = [];
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
