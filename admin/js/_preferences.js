/*global $, getData */
'use strict';

$(function() {
  if ($('#new_pwd').length == 0) {
    return;
  }
  const texts = getData('preferences');
  $('#new_pwd').pwstrength({
    texts: texts
  });
  const user_email = $('#user_email').val();
  $('#user-form').submit(function() {
    var e = this.elements.cur_pwd;
    if (e.value != '') {
      return true;
    }
    if ($('#user_email').val() != user_email || $('#new_pwd').val() != '') {
      $(e).addClass('missing').focusout(function() {
        $(this).removeClass('missing');
      });
      e.focus();
      return false;
    }
    return true;
  });
});
