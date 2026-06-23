/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#user_options_columns_container div').sortable({
    cursor: 'move',
    items: 'div.cols_sort_handler',
  });
  $('#user_options_columns_container div div.cols_sort_handler, #user_options_columns_container div label').css({
    cursor: 'move',
  });
  $('#user_options_columns_container div input').css({
    cursor: 'auto',
  });

  $('#my-favs ul').sortable();
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
