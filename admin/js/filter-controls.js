/*global $, dotclear */
'use strict';

$(() => {
  // Get some DATA
  Object.assign(dotclear.msg, dotclear.getData('filter_controls'));

  let reset_url = '?';
  if (dotclear.filter_reset_url != undefined) {
    reset_url = dotclear.filter_reset_url;
  }

  const $filtersform = $('#filters-form');
  $filtersform.before(
    `<p><a id="filter-control" class="form-control" href="${reset_url}" style="display:inline">${dotclear.msg.filter_posts_list}</a></p>`,
  );

  if (dotclear.msg.show_filters) {
    $('#filter-control').addClass('open').text(dotclear.msg.cancel_the_filter);
  } else {
    $filtersform.hide();
  }
  if (dotclear.getData('filter_options').auto_filter) {
    $('#filters-form input[type="submit"]').parent().hide();
    $('#filters-form select').on('input', () => {
      $filtersform[0].submit();
    });
    $('#filters-form input[type!="submit"]').on('focusin', function () {
      $(this).data('val', $(this).val());
    });
    $('#filters-form input[type!="submit"]').on('focusout', function () {
      if ($(this).val() !== $(this).data('val')) {
        $filtersform[0].submit();
      }
    });
  }

  // Deal with enter key on filters form : every form element will be filtered but Cancel button
  dotclear.enterKeyInForm('#filters-form', '#filters-form input[type="submit"]', '#filter-control');

  $('#filter-control').on('click', function () {
    if ($(this).hasClass('open')) {
      if (dotclear.msg.show_filters) {
        return true;
      }
      $filtersform.hide();
      $(this).removeClass('open').text(dotclear.msg.filter_posts_list);
    } else {
      $filtersform.show();
      $(this).addClass('open').text(dotclear.msg.cancel_the_filter);
    }
    return false;
  });

  $('#filter-options-save').on('click', () => {
    // Save list options (via services)
    const param = {
      f: 'setListsOptions',
      xd_check: dotclear.nonce,
      id: $('#filters-options-id').val(),
      sort: $('#sortby').val(),
      order: $('#order').val(),
      nb: $('#nb').val(),
    };
    $.post('services.php', param)
      .done((data) => {
        const rsp = $(data).children('rsp')[0];
        if (rsp) {
          const res = $(rsp).find('result')[0];
          if (res) {
            window.alert(res.getAttribute('msg'));
          } else if (rsp.getAttribute('status') !== 'ok') {
            window.console.log('Dotclear REST server error');
          }
        }
      })
      .fail((jqXHR, textStatus, errorThrown) => {
        // No response
        window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
        window.alert('Server error');
      });
  });
});
