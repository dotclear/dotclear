/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  for (const elt of document.querySelectorAll('.checkboxes-helpers')) {
    dotclear.checkboxesHelpers(elt, undefined, '#links-form td input[type=checkbox]', '#links-form #remove-action');
  }
  dotclear.condSubmit('#links-form td input[type="checkbox"]', '#links-form #remove-action');

  const msg = dotclear.getData('blogroll');
  document.querySelector('#links-form #remove-action')?.addEventListener('click', (event) => {
    if (window.confirm(msg.confirm_links_delete)) return true;
    event.preventDefault();
    return false;
  });
});
