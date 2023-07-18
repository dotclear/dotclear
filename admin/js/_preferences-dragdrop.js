/*global $ */
'use strict';

$(() => {
  $('#user_options_columns div').sortable({
    cursor: 'move',
    items: '> label',
  });
  $('#my-favs ul').sortable({
    cursor: 'move',
  });
  $('#my-favs ul, #my-favs ul *').css({
    cursor: 'move',
  });
  $('#my-favs ul input').css({
    cursor: 'auto',
  });
  $('#favs-form').on('submit', () => {
    const order = [];
    $('#my-favs ul li input.position').each(function () {
      order.push(this.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    });
    $('input[name=favs_order]')[0].value = order.join(',');
    return true;
  });
  $('#my-favs ul li input.position').hide();
});
