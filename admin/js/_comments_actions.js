/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready

  dotclear.condSubmit('table.posts-list td input[type=checkbox]', 'input[type=submit]');
});
