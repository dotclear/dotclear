/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  dotclear.condSubmit('table.blogs-list td input[type=checkbox]', 'input[type=submit]');
});
