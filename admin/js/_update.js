/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready

  // Confirm backup deletion
  document.querySelector('input[type=submit][name=b_del]')?.addEventListener('click', (event) => {
    if (!window.confirm(dotclear.msg.confirm_delete_backup)) {
      event.preventDefault();
      return false;
    }
    return true;
  });

  // Confirm backup revert
  document.querySelector('input[type=submit][name=b_revert]')?.addEventListener('click', (event) => {
    if (!window.confirm(dotclear.msg.confirm_revert_backup)) {
      event.preventDefault();
      return false;
    }
    return true;
  });
});
