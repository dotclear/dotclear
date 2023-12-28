/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.querySelectorAll('a.uninstall_module_button').forEach((element) => {
    element.parentNode.querySelector('input.delete')?.replaceWith(element);
  });
});
