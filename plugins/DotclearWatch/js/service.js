/*global $, dotclear */
'use strict';

dotclear.adminDotclearWatchSendReport = () => {
  dotclear.services(
    'adminDotclearWatchSendReport',
    (data) => {
      try {
        const response = JSON.parse(data);
        if (response?.success) {
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
  dotclear.adminDotclearWatchSendReport();
});
