/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  dotclear.condSubmit('table.posts-list td input[type=checkbox]', 'input[type=submit]');
});
