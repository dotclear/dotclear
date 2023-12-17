/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const ads_id = 'WJxYFNKPMRlS';

  // Check adblocker helper
  dotclear.adblockCheck = (msg) => {
    const ads = document.getElementById(ads_id);
    const adblocker_on = ads === null || window.getComputedStyle(ads).display === 'none';
    if (msg && adblocker_on) {
      // An adblocker has remove the pseudo advertising block or
      // An adblocker has set display property of the pseudo advertising block to none
      window.alert(msg);
    }
    // Remove pseudo advertising block in page
    if (ads !== null) {
      ads.remove();
    }

    return adblocker_on;
  };

  // Create pseudo advertising block in page
  const spot = document.createElement('div');
  if (!spot) return;

  spot.id = ads_id;
  spot.classList.add('adsbygoogle');
  spot.classList.add('adsbox');
  spot.innerHTML = '&nbsp;';
  document.body.appendChild(spot);

  new Promise((resolve) => setTimeout(resolve, 1000)).then(() => dotclear.adblockCheck(dotclear.msg.adblocker));
});
