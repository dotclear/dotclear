'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const prelude = document.querySelector('#prelude');
  if (prelude) {
    const links = prelude.querySelectorAll('a');
    links.forEach((link) => {
      link.classList.add('hidden');
      link.addEventListener('focus', () => {
        links.forEach((link) => link.classList.remove('hidden'));
      });
    });

    document.querySelector('body').addEventListener('click', (e) => {
      if (e.target.matches('#prelude a[href="#help"]')) {
        e.preventDefault();
        document.querySelector('#help-button a').focus();
      }
    });
  }
});
