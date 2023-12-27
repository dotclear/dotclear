/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('uninstaller'));

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#uninstall-form').on('submit', function () {
    return window.confirm(dotclear.msg.confirm_uninstall);
  });
});
