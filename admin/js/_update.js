/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Confirm backup deletion
  document.querySelector('input[type=submit][name=b_del]')?.addEventListener('click', (event) => {
    if (window.confirm(dotclear.msg.confirm_delete_backup)) return true;
    event.preventDefault();
    return false;
  });

  // Confirm backup revert
  document.querySelector('input[type=submit][name=b_revert]')?.addEventListener('click', (event) => {
    if (window.confirm(dotclear.msg.confirm_revert_backup)) return true;
    event.preventDefault();
    return false;
  });
});
