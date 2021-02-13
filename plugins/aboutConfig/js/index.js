/*global $, dotclear */
'use strict';

$(function () {
  $('#gs_submit').hide();
  $('#ls_submit').hide();
  $('#gs_nav').on('change', function () {
    window.location = $('#gs_nav option:selected').val();
  });
  $('#ls_nav').on('change', function () {
    window.location = $('#ls_nav option:selected').val();
  });
  dotclear.responsiveCellHeaders(document.querySelector('table.settings'), 'table.settings', 0, true);
  $('table.settings').addClass('rch rch-thead');
});
