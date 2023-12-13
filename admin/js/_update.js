/*global dotclear */
'use strict';

window.addEventListener('load', () => {
  // DOM ready and content loaded

  // Confirm backup deletion
  document
    .querySelector('input[type=submit][name=b_del]')
    ?.addEventListener('click', () => window.confirm(dotclear.msg.confirm_delete_backup));

  // Confirm backup revert
  document
    .querySelector('input[type=submit][name=b_revert]')
    ?.addEventListener('click', () => window.confirm(dotclear.msg.confirm_revert_backup));
});
