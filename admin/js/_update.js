/*global $, dotclear */
'use strict';

$(() => {
  $('form input[type=submit][name=b_del]').on('click', () => window.confirm(dotclear.msg.confirm_delete_backup));
  $('form input[type=submit][name=b_revert]').on('click', () => window.confirm(dotclear.msg.confirm_revert_backup));
});
