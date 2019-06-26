/*global $, dotclear, jsToolBar, getData */
'use strict';

Object.assign(dotclear.msg, getData('blog_pref'));

$(function() {
  const blog_url = $('#blog_url');
  if (blog_url.length > 0 && !blog_url.is(':hidden')) {
    const checkQueryString = function() {
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
    blog_url.focusout(checkQueryString);
    $('body').on('change', '#url_scan', checkQueryString);
  }

  $('#date_format_select,#time_format_select').change(function() {
    if ($(this).prop('value') == '') {
      return;
    }
    $('#' + $(this).attr('id').replace('_select', '')).prop('value', $(this).prop('value'));
    $(this).parent().next('.chosen').html($(this).find(':selected').prop('label'));
  });

  // HTML text editor
  if (typeof jsToolBar !== 'undefined' && $.isFunction(jsToolBar)) {
    $('#blog_desc').each(function() {
      let tbWidgetText = new jsToolBar(this);
      tbWidgetText.context = 'blog_desc';
      tbWidgetText.draw('xhtml');
    });
  }

  // Hide advanced and plugins prefs sections
  $('#standard-pref h3').toggleWithLegend($('#standard-pref').children().not('h3'), {
    legend_click: true,
    hide: false
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
