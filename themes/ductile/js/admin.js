/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#stickerslist').sortable();
  for (const element of document.querySelectorAll('#stickerslist tr td input.position')) {
    element.style.display = 'none';
  }
  for (const element of document.querySelectorAll('#stickerslist tr td.handle')) {
    element.classList.add('handler');
  }

  document.getElementById('theme_config')?.addEventListener('submit', () => {
    const order = [];
    for (const element of document.querySelectorAll('#stickerslist tr td input.position')) {
      order.push(element.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    }
    document.querySelector('input[name=ds_order]').value = order.join(',');
    return true;
  });
});
