/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $.pageTabs('two-boxes');
  $('#pageslist').sortable();
  document.querySelectorAll('#pageslist tr td input.position').forEach((element) => {
    element.style.display = 'none';
  });
  document.querySelectorAll('#pageslist tr td.handle').forEach((element) => element.classList.add('handler'));
});
