/*global $ */
'use strict';

$(function() {
  $('#gs_submit').hide();
  $('#ls_submit').hide();
  $('#gs_nav').change(function() {
    window.location = $('#gs_nav option:selected').val();
  });
  $('#ls_nav').change(function() {
    window.location = $('#ls_nav option:selected').val();
  });
});
