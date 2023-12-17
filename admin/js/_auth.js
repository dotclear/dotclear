/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Give focus to user field
  const uid = document.querySelector('input[name=user_id]');
  uid.focus();

  const ckh = document.getElementById('cookie_help');
  if (ckh) ckh.style.display = navigator.cookieEnabled ? 'none' : '';

  const upw = document.querySelector('input[name=user_pwd]');
  if (!upw) {
    return;
  }

  // Add an event listener to capture CR key press in user field to give to password field if it is empty
  uid.addEventListener('keypress', (event) => {
    if (event.which == 13 && upw.value == '') {
      // Password is empty, give focus to it
      upw.focus();
      // Stop handling of this event (CR keypress)
      event.preventDefault();
    }
  });
});
