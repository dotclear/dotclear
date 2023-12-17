'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const prelude = document.querySelector('#prelude');
  if (!prelude) {
    return;
  }
  const links = prelude.querySelectorAll('a');
  links.forEach((link) => {
    link.classList.add('hidden');
    link.addEventListener('focus', () => {
      links.forEach((link) => link.classList.remove('hidden'));
    });
  });

  document.querySelector('body').addEventListener('click', (event) => {
    if (event.target.matches('#prelude a[href="#help"]')) {
      event.preventDefault();
      document.querySelector('#help-button a').focus();
    }
  });
});
