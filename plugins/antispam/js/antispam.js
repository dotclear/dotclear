/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('antispam'));

$(function () {
  $('#filters-list').sortable({
    cursor: 'move',
  });
  $('#filters-list tr')
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
  $('#filters-list-form').on('submit', function () {
    let order = [];
    $('#filters-list tr td input.position').each(function () {
      order.push(this.name.replace(/^f_order\[([^\]]+)\]$/, '$1'));
    });
    $('input[name=filters_order]')[0].value = order.join(',');
    return true;
  });
  $('#filters-list tr td input.position').hide();
  $('#filters-list tr td.handle').addClass('handler');

  $('form input[type=submit][name=delete_all]').on('click', function () {
    return window.confirm(dotclear.msg.confirm_spam_delete);
  });
  dotclear.responsiveCellHeaders(document.querySelector('#filters-list-form table'), '#filters-list-form table', 1, true);
});
