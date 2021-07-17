/*global $, dotclear */
'use strict';

$(function () {
  if ($('#new_pwd').length == 0) {
    return;
  }
  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));
});
