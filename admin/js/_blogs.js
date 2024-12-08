/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  for (const elt of document.querySelectorAll('.checkboxes-helpers')) {
    dotclear.checkboxesHelpers(elt, undefined, '#form-blogs td input[type=checkbox]', '#form-blogs #do-action');
  }
  dotclear.enableShiftClick('#form-blogs td input[type=checkbox]');
  dotclear.condSubmit('#form-blogs td input[type=checkbox]', '#form-blogs #do-action');
  dotclear.responsiveCellHeaders(document.querySelector('#form-blogs table'), '#form-blogs table', 1);

  // If password is mandatory for action, set the field as required
  const password = document.getElementById('pwd');
  const action = document.querySelector('select[name="action"]');
  if (password && action) {
    const setRequired = () => {
      if (action.value === 'delete') password.setAttribute('required', 'required');
      else password.removeAttribute('required');
    };
    setRequired();
    action.addEventListener('change', setRequired);
  }

  // Ask confirmation before blog(s) deletion
  document.getElementById('form-blogs')?.addEventListener('submit', (event) => {
    if (document.querySelector('select[name="action"]')?.value === 'delete') {
      const number = document.querySelectorAll('input[name="blogs[]"]:checked').length;
      if (number) {
        if (window.confirm(dotclear.msg.confirm_delete_blog.replace('%s', number))) return true;
        event.preventDefault();
        return false;
      }
    }
  });
});
