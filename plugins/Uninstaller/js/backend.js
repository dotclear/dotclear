/*global $, dotclear */
'use strict';

$(() => {
  $('a.uninstall_module_button').each(function () {
    $(this).parent().find('input.delete').replaceWith($(this));
  });
});