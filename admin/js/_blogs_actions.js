/*global $, dotclear */
'use strict';

$(() => {
  dotclear.condSubmit('table.blogs-list td input[type=checkbox]', 'input[type=submit]');
});
