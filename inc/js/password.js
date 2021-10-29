/*global dotclear */
'use strict';

(() => {
  // Get locales
  const msg = dotclear.getData('dc_password_msg');

  function togglePasswordHelper(e) {
    e.preventDefault();
    const button = e.currentTarget;
    const isPasswordShown = button.classList.contains('pw-hide');
    const buttonContent = isPasswordShown ? msg.show_password : msg.hide_password;

    button.classList.toggle('pw-hide', !isPasswordShown);
    button.classList.toggle('pw-show', isPasswordShown);

    button.previousElementSibling.setAttribute('type', isPasswordShown ? 'password' : 'text');
    button.setAttribute('title', buttonContent);
    button.querySelector('span').textContent = buttonContent;
  }

  function installPasswordHelpers() {
    // Compose button
    const buttonTemplate = new DOMParser().parseFromString(
      `<button type="button" class="pw-show" title="${msg.show_password}"><span class="sr-only">${msg.show_password}</span></button>`,
      'text/html'
    ).body.firstChild;

    const passwordFields = document.querySelectorAll('input[type=password]');

    for (const passwordField of passwordFields) {
      const button = buttonTemplate.cloneNode(true);
      passwordField.after(button);
      passwordField.classList.add('pwd_helper');
      button.addEventListener('click', togglePasswordHelper);
    }
  }

  window.addEventListener('DOMContentLoaded', installPasswordHelpers);
})();
