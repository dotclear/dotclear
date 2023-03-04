/*global dotclear */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  // DOM ready
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
  const e = document.createElement('div');
  e.id = ads_id;
  e.classList.add('adsbygoogle');
  e.classList.add('adsbox');
  e.innerHTML = '&nbsp;';
  document.body.appendChild(e);
});

window.addEventListener('load', () => {
  // DOM ready and content loaded
  // Check adblocker
  new Promise((resolve) => setTimeout(resolve, 1000)).then(() => dotclear.adblockCheck(dotclear.msg.adblocker));
});
