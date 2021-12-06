/*global $, dotclear */
'use strict';

$(() => {
  const ads_id = 'WJxYFNKPMRlS';
  // Check adblocker helper
  dotclear.adblockCheck = (msg) => {
    if (dotclear.adblocker_check) {
      const ads = document.getElementById(ads_id);
      let adblocker_on = false;
      if (ads === null) {
        // An adblocker has remove the pseudo advertising block
        adblocker_on = true;
      } else if (window.getComputedStyle(ads).display === 'none') {
        // An adblocker has set display property of the pseudo advertising block to none
        adblocker_on = true;
      }
      if (msg && adblocker_on) {
        window.alert(msg);
      }
      // Remove pseudo advertising block in page
      if (ads !== null) {
        ads.remove();
      }

      return adblocker_on;
    }
  };
  // Create pseudo advertising block in page
  const e = document.createElement('div');
  e.id = ads_id;
  e.classList.add('adsbygoogle');
  e.classList.add('adsbox');
  e.innerHTML = '&nbsp;';
  document.body.appendChild(e);
});
