/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Give focus to user field
  /**
   * @type {HTMLInputElement|null}
   */
  const uid = document.querySelector('input[name=user_id]');
  if (uid) uid?.focus();

  /**
   * @type {HTMLElement|null}
   */
  const ckh = document.getElementById('cookie_help');
  if (ckh) ckh.style.display = navigator.cookieEnabled ? 'none' : '';

  // Password strength
  dotclear.passwordStrength(dotclear.getData('pwstrength'));

  /**
   * @type {HTMLInputElement|null}
   */
  const upw = document.querySelector('input[name=user_pwd]');
  if (!upw || !uid) {
    return;
  }

  // Add an event listener to capture Enter key press in user field to give to password field if it is empty
  uid.addEventListener('keypress', (/** @type {KeyboardEvent} */ event) => {
    if (event.key === 'Enter' && upw.value === '') {
      // Password is empty, give focus to it
      upw.focus();
      // Stop handling of this event (Enter key pressed)
      event.preventDefault();
    }
  });
});
