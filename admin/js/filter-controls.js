/*global $, dotclear, getData */
'use strict';

$(function() {
  // Get some DATA
  Object.assign(dotclear.msg, getData('filter_controls'));

  let reset_url = '?';
  if (dotclear.filter_reset_url != undefined) {
    reset_url = dotclear.filter_reset_url;
  }

  const $filtersform = $('#filters-form');
  $filtersform.before(`<p><a id="filter-control" class="form-control" href="${reset_url}" style="display:inline">${dotclear.msg.filter_posts_list}</a></p>`);

  if (!dotclear.msg.show_filters) {
    $filtersform.hide();
  } else {
    $('#filter-control')
      .addClass('open')
      .text(dotclear.msg.cancel_the_filter);
  }

  $('#filter-control').on('click', function() {
    if ($(this).hasClass('open')) {
      if (dotclear.msg.show_filters) {
        return true;
      } else {
        $filtersform.hide();
        $(this).removeClass('open')
          .text(dotclear.msg.filter_posts_list);
      }
    } else {
      $filtersform.show();
      $(this).addClass('open')
        .text(dotclear.msg.cancel_the_filter);
    }
    return false;
  });
});
