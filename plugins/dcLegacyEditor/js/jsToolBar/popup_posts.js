/*global $ */
'use strict';

$(() => {
  $('#link-insert-cancel').on('click', () => {
    window.close();
  });

  $('#form-entries tr>td.maximal>a').on('click', function () {
    // Get post_id
    const tb = window.opener.the_toolbar;
    const { data } = tb.elements.link;

    data.href = tb.stripBaseURL($(this).attr('title'));

    tb.elements.link.fncall[tb.mode].call(tb);
    window.close();
  });
});
