/*global dotclear */
'use strict';

window.addEventListener('load', () => {
  // DOM ready and content loaded

  dotclear.condSubmit('table.posts-list td input[type=checkbox]', 'input[type=submit]');
});
