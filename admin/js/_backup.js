/*global $, dotclear */
'use strict';

$(() => {
    // Confirm backup deletion
    $('input[name="b_del"]').on('click', () => window.confirm(dotclear.msg.confirm_delete_backup));
    $('input[name="b_delall"]').on('click', () => window.confirm(dotclear.msg.confirm_delete_backup));
});
