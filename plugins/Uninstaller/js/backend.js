/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('a.uninstall_module_button').each(function () {
    $(this).parent().find('input.delete').replaceWith($(this));
  });
});
