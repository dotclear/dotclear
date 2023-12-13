/*global dotclear */
'use strict';

window.addEventListener('load', () => {
  // DOM ready and content loaded

  // Confirm backup deletion
  document
    .querySelector('input[name=b_del]')
    ?.addEventListener('click', () => window.confirm(dotclear.msg.confirm_delete_backup));

  // Confirm backups deletion
  document
    .querySelector('input[name=b_delall]')
    ?.addEventListener('click', () => window.confirm(dotclear.msg.confirm_delete_backup));
});
