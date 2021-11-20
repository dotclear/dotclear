/*global $, dotclear */
'use strict';

$(() => {
  if ($('#new_pwd').length == 0) {
    return;
  }
  const user_email = $('#user_email').val();
  $('#user-form').on('submit', function () {
    const e = this.elements.cur_pwd;
    if (e.value != '') {
      return true;
    }
    if ($('#user_email').val() != user_email || $('#new_pwd').val() != '') {
      $(e)
        .addClass('missing')
        .on('focusout', function () {
          $(this).removeClass('missing');
        });
      e.focus();
      return false;
    }
    return true;
  });

  // Check adblocker
  dotclear.adblockCheck(dotclear.msg.adblocker);
  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));
});
