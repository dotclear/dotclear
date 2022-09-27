/*global $, dotclear */
'use strict';

$(() => {
  const move = (select) => {
    const id = $(`${select} option:selected`).val();
    window.location = id;
    document.getElementById(id.substring(1)).caption?.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
  };
  $('#gp_submit,#lp_submit').hide();
  $('#gp_nav').on('change', () => {
    move('#gp_nav');
  });
  $('#lp_nav').on('change', () => {
    move('#lp_nav');
  });
  dotclear.responsiveCellHeaders(document.querySelector('table.prefs'), 'table.prefs', 0, true);
  $('table.prefs').addClass('rch rch-thead');
});
