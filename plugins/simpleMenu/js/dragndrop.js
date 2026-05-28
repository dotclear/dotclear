/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#menuitemslist').sortable({ handle: '.handle' });
  for (const element of document.querySelectorAll('#menuitemslist tr td input.position')) {
    element.style.display = 'none';
  }
  for (const element of document.querySelectorAll('#menuitemslist tr td.handle')) {
    element.classList.add('handler');
  }
});
