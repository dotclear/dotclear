/*global dotclear */
'use strict';

window.addEventListener('load', () => {
  // DOM ready and content loaded

  dotclear.condSubmit('table.blogs-list td input[type=checkbox]', 'input[type=submit]');
});
