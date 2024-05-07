/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const prelude = document.querySelector('#prelude');
  if (!prelude) {
    return;
  }
  prelude.classList.add('hidden');
  const links = prelude.querySelectorAll('a');
  for (const link of links) {
    link.addEventListener('focus', () => {
      prelude.classList.remove('hidden');
    });
  }

  document.querySelector('body').addEventListener('click', (event) => {
    if (event.target.matches('#prelude a[href="#help"]')) {
      event.preventDefault();
      document.querySelector('#help-button a').focus();
    }
  });
});
