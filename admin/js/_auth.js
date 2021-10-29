/*global $, dotclear */
'use strict';

$(window).on('load', () => {
  const uid = $('input[name=user_id]');
  const upw = $('input[name=user_pwd]');
  uid.trigger('focus');

  if (upw.length == 0) {
    return;
  }
  uid.keypress((evt) => {
    if (evt.which == 13 && upw.val() == '') {
      upw.trigger('focus');
      return false;
    }
    return true;
  });

  if (navigator.cookieEnabled) {
    $('#cookie_help').hide();
  } else {
    $('#cookie_help').show();
  }
});

$(() => {
  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));
});
