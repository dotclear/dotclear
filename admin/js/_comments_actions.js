/*global $, dotclear */
'use strict';

$(() => {
  dotclear.condSubmit('table.posts-list td input[type=checkbox]', 'input[type=submit]');
});
