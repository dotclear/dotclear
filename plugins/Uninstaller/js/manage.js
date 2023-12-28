/*global dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('uninstaller'));

dotclear.ready(() => {
  // DOM ready and content loaded

  document.getElementById('uninstall-form')?.addEventListener(() => window.confirm(dotclear.msg.confirm_uninstall));
});
