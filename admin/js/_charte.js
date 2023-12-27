/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $.pageTabs('two-boxes');
  $('#pageslist').sortable();
  $('#pageslist tr td input.position').hide();
  $('#pageslist tr td.handle').addClass('handler');
});
