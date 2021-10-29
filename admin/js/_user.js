/*global $, dotclear */
'use strict';

$(() => {
  if ($('#new_pwd').length == 0) {
    return;
  }
  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));
});
