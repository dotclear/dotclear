/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.querySelectorAll('.checkboxes-helpers').forEach((elt) => {
    dotclear.checkboxesHelpers(elt, undefined, '#form-blogs td input[type=checkbox]', '#form-blogs #do-action');
  });
  dotclear.enableShiftClick('#form-blogs td input[type=checkbox]');
  dotclear.condSubmit('#form-blogs td input[type=checkbox]', '#form-blogs #do-action');
  dotclear.responsiveCellHeaders(document.querySelector('#form-blogs table'), '#form-blogs table', 1);
  $('#form-blogs').on('submit', function (event) {
    if ($(this).find('select[name="action"]').val() == 'delete') {
      if (!window.confirm(dotclear.msg.confirm_delete_blog.replace('%s', $('input[name="blogs[]"]:checked').length))) {
        event.preventDefault();
        return false;
      }
      return true;
    }
  });
});
