/*global $, dotclear, jsToolBar */
'use strict';

dotclear.dbStoreUpdate = (store, icon) => {
  dotclear.jsonServicesPost(
    'checkStoreUpdate',
    (data) => {
      if (!data.check) {
        return;
      }
      // Something has to be displayed
      // update link to details
      icon.children('a').attr('href', `${icon.children('a').attr('href')}#update`);
      // update icon
      icon
        .children('a')
        .children('img')
        .attr(
          'src',
          icon
            .children('a')
            .children('img')
            .attr('src')
            .replace(/([^\/]+)(\..*)$/g, '$1-update$2'),
        );
      // add icon text says there is an update
      icon.children('a').children('.db-icon-title').append('<br />').append(data.ret);
      // Badge (info) on dashboard icon
      dotclear.badge(icon, {
        id: `mu-${store}`,
        value: data.nb,
        icon: true,
        type: 'info',
      });
    },
    { store },
  );
};
$(() => {
  // check if core update available
  dotclear.jsonServicesGet('checkCoreUpdate', (data) => {
    if (data.check) {
      // Something has to be displayed
      $('#content h2').after(data.ret);
      // manage outgoing links
      dotclear.outgoingLinks('#ajax-update a');
    }
  });
});
