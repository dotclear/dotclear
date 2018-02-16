/*global $, dotclear */
'use strict';

$(function() {
  $('.checkboxes-helpers').each(function() {
    dotclear.checkboxesHelpers(this, undefined, '#form-blogs td input[type=checkbox]', '#form-blogs #do-action');
  });
  $('#form-blogs td input[type=checkbox]').enableShiftClick();
  dotclear.condSubmit('#form-blogs td input[type=checkbox]', '#form-blogs #do-action');
  $('#form-blogs').submit(function() {
    var action = $(this).find('select[name="action"]').val();
    if (action == 'delete') {
      return window.confirm(dotclear.msg.confirm_delete_blog.replace('%s', $('input[name="blogs[]"]:checked').size()));
    }
  });
});
