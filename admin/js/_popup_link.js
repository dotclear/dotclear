/*global $, dotclear */
'use strict';

$(function() {
  // Set focus on #href input
  $('#href').focus();

  // Deal with enter key on link insert popup form : every form element will be filtered but Cancel button
  dotclear.enterKeyInForm('#link-insert-form', '#link-insert-ok', '#link-insert-cancel');
});
