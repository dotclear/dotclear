/*global $, getData */
'use strict';

$(function () {
  const login_re = new RegExp('[^A-Za-z0-9@._-]+', 'g');
  $('#u_firstname').on('keyup', function () {
    $('#u_login').val(this.value.toLowerCase().replace(login_re, '').substring(0, 32));
  });
  $('#u_login').on('keyup', function () {
    $(this).val(this.value.replace(login_re, ''));
  });

  const texts = getData('install');
  $('#u_pwd').pwstrength({
    texts: texts,
  });

  $('#u_login')
    .parent()
    .after($('<input type="hidden" name="u_date" value="' + Date().toLocaleString() + '" />'));

  const show = getData('install_show');
  const password_link = $('<a href="#" id="obfus">' + show + '</a>').on('click', function () {
    $('#password').show();
    $(this).remove();
    return false;
  });
  $('#password').hide().before(password_link);
});
