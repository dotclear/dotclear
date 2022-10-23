/*global $, dotclear */
'use strict';

$(() => {
  const ads_id = 'WJxYFNKPMRlS';

  // Check adblocker helper
  dotclear.adblockCheck = (msg) => {
    const ads = document.getElementById(ads_id);
    let adblocker_on = false;
    if (ads === null || window.getComputedStyle(ads).display === 'none') {
      // An adblocker has remove the pseudo advertising block or
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
  };

  // Create pseudo advertising block in page
  const e = document.createElement('div');
  e.id = ads_id;
  e.classList.add('adsbygoogle');
  e.classList.add('adsbox');
  e.innerHTML = '&nbsp;';
  document.body.appendChild(e);

  // Check adblocker
  new Promise((resolve) => setTimeout(resolve, 1000)).then(() => dotclear.adblockCheck(dotclear.msg.adblocker));
});
