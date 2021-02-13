/*global $, dotclear */
'use strict';

$(function () {
  dotclear.condSubmit('table.posts-list td input[type=checkbox]', 'input[type=submit]');
});
