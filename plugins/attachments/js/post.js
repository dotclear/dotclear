/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  dotclear.toggleWithLegend(document.querySelector('h5.s-attachments'), document.querySelectorAll('.s-attachments:not(h5)'), {
    user_pref: 'dcx_attachments',
    legend_click: true,
  });
});
