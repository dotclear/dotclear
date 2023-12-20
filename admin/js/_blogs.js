/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  document.querySelectorAll('.checkboxes-helpers').forEach((elt) => {
    dotclear.checkboxesHelpers(elt, undefined, '#form-blogs td input[type=checkbox]', '#form-blogs #do-action');
  });
  dotclear.enableShiftClick('#form-blogs td input[type=checkbox]');
  dotclear.condSubmit('#form-blogs td input[type=checkbox]', '#form-blogs #do-action');
  dotclear.responsiveCellHeaders(document.querySelector('#form-blogs table'), '#form-blogs table', 1);

  // Ask confirmation before blog(s) deletion
  document.getElementById('form-blogs')?.addEventListener('submit', (event) => {
    if (document.querySelector('select[name="action"]')?.value === 'delete') {
      const number = document.querySelectorAll('input[name="blogs[]"]:checked').length;
      if (number) {
        if (!window.confirm(dotclear.msg.confirm_delete_blog.replace('%s', number))) {
          event.preventDefault();
          return false;
        }
        return true;
      }
    }
  });
});
