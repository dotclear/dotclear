/*global $, dotclear */
'use strict';

$(function () {
  $('#gp_submit,#lp_submit').hide();
  $('#part-local,#part-global').on('tabload', function () {
    $('.multi-part.active select.navigation option:first').attr('selected', true);
  });
  $('#gp_nav').on('change', function () {
    window.location = $('#gp_nav option:selected').val();
  });
  $('#lp_nav').on('change', function () {
    window.location = $('#lp_nav option:selected').val();
  });
  dotclear.responsiveCellHeaders(document.querySelector('table.prefs'), 'table.prefs', 0, true);
  $('table.prefs').addClass('rch rch-thead');
});
