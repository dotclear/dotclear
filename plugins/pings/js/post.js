/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('edit-entry')?.addEventListener('onetabload', () => {
    const services = document.querySelectorAll('p.ping-services');
    if (services.length > 0) {
      const para = document.createElement('p');
      if (para) {
        para.classList.add('ping-services');
        services[services.length - 1].after(para);
        dotclear.checkboxesHelpers(para, document.querySelectorAll('.check-ping-services'));
      }
    }
    const title = document.querySelector('h5.ping-services');
    if (title) {
      const siblings = document.querySelectorAll('p.ping-services');
      if (siblings)
        dotclear.toggleWithLegend(title, siblings, {
          user_pref: 'dcx_ping_services',
          legend_click: true,
        });
    }
  });
});
