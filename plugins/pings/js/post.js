/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#edit-entry').on('onetabload', () => {
    if ($('p.ping-services').length > 0) {
      const p = $('<p></p>');
      p.addClass('ping-services');
      $('p.ping-services:last').after(p);
      dotclear.checkboxesHelpers($('p.ping-services:last').get(0), $('.check-ping-services'));
    }
    $('h5.ping-services').toggleWithLegend($('p.ping-services'), {
      user_pref: 'dcx_ping_services',
      legend_click: true,
    });
  });
});
