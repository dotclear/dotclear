/*global $, dotclear */
'use strict';

$(() => {
  if ($.fn.nestedSortable !== undefined) {
    $('#categories ul li').css('cursor', 'move');
    $('#save-set-order').prop('disabled', true).addClass('disabled');
    $('#categories ul').nestedSortable({
      listType: 'ul',
      items: 'li',
      placeholder: 'placeholder',
      update() {
        $('#categories_order').attr('value', JSON.stringify($('#categories ul').nestedSortable('toArray')));
        $('#save-set-order').prop('disabled', false).removeClass('disabled');
      },
    });
  }

  $('input[name^="delete"]').on('click', function () {
    return window.confirm(
      dotclear.msg.confirm_delete_category.replace('%s', $(this).parents('li').first().find('.cat-title label a').text())
    );
  });

  $('input[name="reset"]').on('click', () => window.confirm(dotclear.msg.confirm_reorder_categories));
});
