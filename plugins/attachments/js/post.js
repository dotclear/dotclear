/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('h5.s-attachments').toggleWithLegend($('.s-attachments').not('h5'), {
    user_pref: 'dcx_attachments',
    legend_click: true,
  });
});
