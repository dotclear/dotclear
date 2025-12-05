/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  if (!document.querySelector('#filters-list')) {
    return;
  }

  const msg = dotclear.getData('antispam');
  document.querySelector('form input[type=submit][name=delete_all]')?.addEventListener('click', (event) => {
    if (window.confirm(msg.confirm_spam_delete)) return true;
    event.preventDefault();
    return false;
  });

  // Prepare mobile display for tables
  dotclear.responsiveCellHeaders(document.querySelector('#filters-list-form table'), '#filters-list-form table', 1, true);
});
