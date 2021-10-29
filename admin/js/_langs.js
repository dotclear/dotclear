/*global $, dotclear */
'use strict';

$(() => {
  $('table.plugins form input[type=submit][name=delete]').on('click', function () {
    const l_name = $(this).parents('tr.line').find('td:first').text();
    return window.confirm(dotclear.msg.confirm_delete_lang.replace('%s', l_name));
  });
});
