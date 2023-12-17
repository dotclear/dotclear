/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Confirm backup deletion
  document.querySelector('input[name=b_del]')?.addEventListener('click', (event) => {
    if (!window.confirm(dotclear.msg.confirm_delete_backup)) {
      event.preventDefault();
      return false;
    }
    return true;
  });

  // Confirm backups deletion
  document.querySelector('input[name=b_delall]')?.addEventListener('click', (event) => {
    if (!window.confirm(dotclear.msg.confirm_delete_backup)) {
      event.preventDefault();
      return false;
    }
    return true;
  });
});
