/*global jQuery, dotclear */
'use strict';
dotclear.ready(() => {
  const user_image_select = document.querySelector('#user_image_selector');
  const user_image_reset = document.querySelector('#user_image_reset');
  if (user_image_select && user_image_reset) {
    // Logo selector management
    user_image_select.addEventListener('click', (event) => {
      window.open(
        'index.php?process=Media&plugin_id=admin.blog.theme&popup=1&select=1',
        'dc_popup',
        'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,menubar=no,resizable=yes,scrollbars=yes,status=no',
      );
      event.preventDefault();
      return false;
    });
    // Logo reset
    user_image_reset.addEventListener('click', () => {
      const theme_url = document.querySelector('input[name="theme-url"]')?.value;
      const url = `${theme_url}img/logo-ductile.svg`;
      document.querySelector('#user_image_src').setAttribute('src', url);
      document.querySelector('#user_image').value = 'img/logo-ductile.svg';
    });
  }

  // Stickers management
  jQuery('#stickerslist').sortable({ handle: '.handle' });
  for (const element of document.querySelectorAll('#stickerslist tr td input.position')) {
    // Hide input position
    element.style.display = 'none';
  }
  for (const element of document.querySelectorAll('#stickerslist tr td.handle')) {
    // Show handler
    element.classList.add('handler');
  }
});
