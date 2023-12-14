/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready

  if (!document.getElementById('new_pwd')) {
    return;
  }
  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));
});
