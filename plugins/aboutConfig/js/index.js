/*global $, dotclear */
'use strict';

$(() => {
  const move = (select) => {
    const id = $(`${select} option:selected`).val();
    window.location = id;
    document.getElementById(id.substring(1)).caption?.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
  };
  $('#gs_submit').hide();
  $('#ls_submit').hide();
  $('#gs_nav').on('change', () => {
    move('#gs_nav');
  });
  $('#ls_nav').on('change', () => {
    move('#ls_nav');
  });
  dotclear.responsiveCellHeaders(document.querySelector('table.settings'), 'table.settings', 0, true);
  $('table.settings').addClass('rch rch-thead');
});
