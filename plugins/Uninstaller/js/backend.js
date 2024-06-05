/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  for (const element of document.querySelectorAll('a.uninstall_module_button')) {
    element.parentNode.querySelector('input.delete')?.replaceWith(element);
  }
});
