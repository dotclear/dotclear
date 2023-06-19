/*global $, dotclear */
'use strict';

dotclear.dcMaintenanceTaskExpired = () => {
  dotclear.services(
    'dcMaintenanceTaskExpired',
    (data) => {
      try {
        const response = JSON.parse(data);
        if (response?.success) {
          if (response?.payload.ret) {
            const nb_expired = response.payload.nb;
            if (nb_expired !== undefined && nb_expired != dotclear.dcMaintenanceTaskExpired_Count) {
              dotclear.badge($('#dashboard-main #icons p #icon-process-maintenance-fav'), {
                id: 'dcmte',
                remove: nb_expired == 0,
                value: nb_expired,
                sibling: true,
                icon: true,
                type: 'info',
              });
              dotclear.dcMaintenanceTaskExpired_Count = nb_expired;
            }
          }
        } else {
          console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
          return;
        }
      } catch (e) {
        console.log(e);
      }
    },
    (error) => {
      console.log(error);
    },
    true, // Use GET method
    { json: 1 },
  );
};

$(() => {
  // First pass
  dotclear.dcMaintenanceTaskExpired();
  // Auto refresh requested : Set 300 seconds interval between two checks for expired maintenance task counter
  dotclear.dcMaintenanceTaskExpired_Timer = setInterval(dotclear.dcMaintenanceTaskExpired, 300 * 1000);
});
