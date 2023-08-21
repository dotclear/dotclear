/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('uninstaller'));

$(() => {
  $('#uninstall-form').on('submit', function () {
      return window.confirm(dotclear.msg.confirm_uninstall);
  });
});