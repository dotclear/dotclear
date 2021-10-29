/*global $, dotclear */
'use strict';

$(() => {
  $('#gs_submit').hide();
  $('#ls_submit').hide();
  $('#gs_nav').on('change', () => {
    window.location = $('#gs_nav option:selected').val();
  });
  $('#ls_nav').on('change', () => {
    window.location = $('#ls_nav option:selected').val();
  });
  dotclear.responsiveCellHeaders(document.querySelector('table.settings'), 'table.settings', 0, true);
  $('table.settings').addClass('rch rch-thead');
});
