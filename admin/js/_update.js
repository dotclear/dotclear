/*global $, dotclear */
'use strict';

$(function () {
  $('form input[type=submit][name=b_del]').on('click', function () {
    return window.confirm(dotclear.msg.confirm_delete_backup);
  });
  $('form input[type=submit][name=b_revert]').on('click', function () {
    return window.confirm(dotclear.msg.confirm_revert_backup);
  });
});
