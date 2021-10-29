/*global $, dotclear */
'use strict';

$(() => {
  const login_re = new RegExp('[^A-Za-z0-9@._-]+', 'g');
  $('#u_firstname').on('keyup', function () {
    $('#u_login').val(this.value.toLowerCase().replace(login_re, '').substring(0, 32));
  });
  $('#u_login').on('keyup', function () {
    $(this).val(this.value.replace(login_re, ''));
  });

  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));

  $('#u_login')
    .parent()
    .after($(`<input type="hidden" name="u_date" value="${Date().toLocaleString()}" />`));

  const show = dotclear.getData('install_show');
  const password_link = $(`<a href="#" id="obfus">${show}</a>`).on('click', function () {
    $('#password').show();
    $(this).remove();
    return false;
  });
  $('#password').hide().before(password_link);
});
