/*global $ */
'use strict';

$(() => {
  $('#link-insert-cancel').on('click', () => {
    window.close();
  });

  $('#link-insert-ok').on('click', () => {
    sendClose();
    window.close();
  });

  function sendClose() {
    const insert_form = $('#link-insert-form').get(0);
    if (insert_form == undefined) {
      return;
    }

    const tb = window.opener.the_toolbar;
    const { data } = tb.elements.link;

    data.href = tb.stripBaseURL(insert_form.elements.href.value);
    data.title = insert_form.elements.title.value;
    data.hreflang = insert_form.elements.hreflang.value;
    tb.elements.link.fncall[tb.mode].call(tb);
  }
});
