/*global $, dotclear */
'use strict';

$(function () {
  if ($('#new_pwd').length == 0) {
    return;
  }
  // Password strength
  const opts = dotclear.getData('pwstrength');
  dotclear.passwordStrength(opts);
});
