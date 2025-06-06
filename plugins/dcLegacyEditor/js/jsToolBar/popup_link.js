/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('link-insert-cancel')?.addEventListener('click', () => {
    window.close();
  });

  document.getElementById('link-insert-ok')?.addEventListener('click', () => {
    sendClose();
    window.close();
  });

  function sendClose() {
    const form = document.getElementById('link-insert-form');
    if (!form) {
      return;
    }

    const tb = window.opener.the_toolbar;
    const { data } = tb.elements.link;

    data.href = tb.stripBaseURL(form.elements.href.value);
    data.title = form.elements.title.value;
    data.hreflang = form.elements.hreflang.value;
    tb.elements.link.fncall[tb.mode].call(tb);
  }
});
