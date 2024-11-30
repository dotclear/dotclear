/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const deleteButtons = document.querySelectorAll('table.langs form input[type=submit][name=delete]');
  for (const button of deleteButtons) {
    button.addEventListener('click', (event) => {
      const l_name = button.closest('tr.line').querySelector('td:first-child').textContent;
      if (window.confirm(dotclear.msg.confirm_delete_lang.replace('%s', l_name))) return true;
      event.preventDefault();
      return false;
    });
  }
});
