/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('link-insert-cancel')?.addEventListener('click', () => {
    window.close();
  });

  for (const line of document.querySelectorAll('#form-entries tr>td.maximal>a')) {
    line.addEventListener('click', (event) => {
      // Get post_id
      const tb = window.opener.the_toolbar;
      const { data } = tb.elements.link;

      data.href = tb.stripBaseURL(event.target.getAttribute('title'));

      tb.elements.link.fncall[tb.mode].call(tb);
      window.close();
    });
  }
});
