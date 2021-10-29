/*global $, dotclear, jsToolBar */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('blog_pref'));

$(() => {
  const blog_url = $('#blog_url');
  if (blog_url.length > 0 && !blog_url.is(':hidden')) {
    const checkQueryString = () => {
      const url = blog_url[0].value;
      const scan = $('#url_scan')[0].value;
      let msg = '';
      if (/.*[^\/]$/.exec(url) && scan == 'path_info') {
        msg = dotclear.msg.warning_path_info;
      } else if (/.*[^\?]$/.exec(url) && scan == 'query_string') {
        msg = dotclear.msg.warning_query_string;
      }
      $('p#urlwarning').remove();
      if (msg != '') {
        blog_url.parents('p').after(`<p id="urlwarning" class="warning">${msg}</p>`);
      }
    };
    checkQueryString();
    blog_url.on('focusout', checkQueryString);
    $('body').on('change', '#url_scan', checkQueryString);
  }

  $('#date_format_select,#time_format_select').on('change', function () {
    if ($(this).prop('value') == '') {
      return;
    }
    $(`#${$(this).attr('id').replace('_select', '')}`).prop('value', $(this).prop('value'));
    $(this).parent().next('.chosen').html($(this).find(':selected').prop('label'));
  });

  $('#static_home_url_selector').on('click', (e) => {
    window.open(
      'popup_posts.php?plugin_id=admin.blog_pref&type=page',
      'dc_popup',
      'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,menubar=no,resizable=yes,scrollbars=yes,status=no'
    );
    e.preventDefault();
    return false;
  });

  // HTML text editor
  if (typeof jsToolBar === 'function') {
    $('#blog_desc').each(function () {
      const tbWidgetText = new jsToolBar(this);
      tbWidgetText.context = 'blog_desc';
      tbWidgetText.draw('xhtml');
    });
  }

  // Hide advanced and plugins prefs sections
  $('#standard-pref h3').toggleWithLegend($('#standard-pref').children().not('h3'), {
    legend_click: true,
    user_pref: 'dcx_blog_pref_std',
  });
  $('#advanced-pref h3').toggleWithLegend($('#advanced-pref').children().not('h3'), {
    legend_click: true,
    user_pref: 'dcx_blog_pref_adv',
  });
  $('#plugins-pref h3').toggleWithLegend($('#plugins-pref').children().not('h3'), {
    legend_click: true,
    user_pref: 'dcx_blog_pref_plg',
  });
});
