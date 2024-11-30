/*global dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('uninstaller'));

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('uninstall-form')?.addEventListener('submit', (event) => {
    if (window.confirm(dotclear.msg.confirm_uninstall)) return true;
    event.preventDefault();
    return false;
  });
});
