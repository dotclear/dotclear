/*global $ */
'use strict';

$(() => {
  $('h5.s-attachments').toggleWithLegend($('.s-attachments').not('h5'), {
    user_pref: 'dcx_attachments',
    legend_click: true,
  });
});
