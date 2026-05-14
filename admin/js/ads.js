/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM content loaded

  const bait_id = 'adTest';

  // Create pseudo advertising block in page
  const bait = document.createElement('div');
  if (!bait) return;

  const bait_ids = ['AdHeader', 'AdContainer', 'AD_Top', 'homead', 'ad-lead'];
  const generatesBanners = () =>
    bait_ids.map((bannerId) => {
      const div = document.createElement('div');
      div.setAttribute('id', bannerId);
      return div;
    });

  bait.id = bait_id;
  bait.classList.add(
    'ad',
    'adbadge',
    'ads',
    'adsbox',
    'adsbygoogle',
    'ad-box',
    'ad-placement',
    'ad-placeholder',
    'ad-wrapper',
    'BannerAd',
    'doubleclick',
    'pub_300x250',
    'pub_300x250m',
    'pub_728x90',
    'textAd',
    'text-ad',
    'text-ad-links',
    'text-ads',
    'text_ad',
    'text_ads',
  );
  bait.style.cssText +=
    'width: 1px !important; height: 1px !important; position: absolute !important; left: -10000px !important; top: -1000px !important;';
  bait.setAttribute('aria-hidden', 'true');
  bait.textContent = '\xa0';
  bait.append(...generatesBanners());
  document.body.appendChild(bait);

  // Check adblocker helper
  dotclear.adblockCheck = (msg) => {
    const ads = document.getElementById(bait_id);
    const adblocker_on =
      ads === null ||
      globalThis.getComputedStyle(ads).display === 'none' ||
      globalThis.getComputedStyle(ads).display === 'hidden';
    if (msg && adblocker_on) {
      // An adblocker has remove the pseudo advertising block or
      // An adblocker has set display property of the pseudo advertising block to none
      globalThis.alert(msg);
    }
    // Remove pseudo advertising block in page
    if (ads !== null) {
      ads.remove();
    }

    return adblocker_on;
  };

  new Promise((resolve) => setTimeout(resolve, 1000)).then(() => dotclear.adblockCheck(dotclear.msg.adblocker));
});
