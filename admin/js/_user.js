/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  if (!document.getElementById('new_pwd')) {
    return;
  }
  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));
});
