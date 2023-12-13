/*global dotclear */
'use strict';

window.addEventListener('load', () => {
  // DOM ready and content loaded
  if (!document.getElementById('new_pwd')) {
    return;
  }
  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));
});
