/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  for (const elt of document.querySelectorAll('.checkboxes-helpers')) {
    dotclear.checkboxesHelpers(elt, undefined, '#links-form td input[type=checkbox]', '#links-form #do-action');
  }
  dotclear.condSubmit('#links-form td input[type="checkbox"]', '#links-form #do-action');

  const msg = dotclear.getData('blogroll');
  document.querySelector('#links-form #do-action')?.addEventListener('click', (event) => {
    const action = document.querySelector('#links-form #action');
    if (action.value !== 'delete') {
      return;
    }
    if (window.confirm(msg.confirm_links_delete)) return true;
    event.preventDefault();
    return false;
  });
});
