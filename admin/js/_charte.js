/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  dotclear.pageTabs('two-boxes');
  $('#pageslist').sortable();
  for (const element of document.querySelectorAll('#pageslist tr td input.position')) {
    element.style.display = 'none';
  }
  for (const element of document.querySelectorAll('#pageslist tr td.handle')) {
    element.classList.add('handler');
  }
});
