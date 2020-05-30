/*global dotclear */
'use strict';

window.addEventListener('load', () => {
  // Get locales
  const msg = dotclear.getData('dc_password_msg');

  // Compose button
  let xml = new DOMParser().parseFromString(
    `<button type="button" class="pw-show" title="${msg.show_password}"><span class="sr-only">${msg.show_password}</span></button>`,
    'text/html'
  ).body.firstChild;

  const pwd = document.querySelectorAll('input[type=password]');
  pwd.forEach(function(element) {
    element.insertAdjacentElement('afterend', xml);
    element.nextElementSibling.addEventListener('click', function(e) {
      if (this.classList.contains('pw-show')) {
        this.classList.remove('pw-show');
        this.classList.add('pw-hide');
        this.previousElementSibling.setAttribute('type', 'text');
        this.setAttribute('title', msg.hide_password);
        this.querySelector('span').textContent = msg.hide_password;
      } else {
        this.classList.remove('pw-hide');
        this.classList.add('pw-show');
        this.previousElementSibling.setAttribute('type', 'password');
        this.setAttribute('title', msg.show_password);
        this.querySelector('span').textContent = msg.show_password;
      }
      e.preventDefault();
    });
  });

});
