/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#stickerslist').sortable();
  document.querySelectorAll('#stickerslist tr td.handle').forEach((element) => element.classList.add('handler'));

  const positions = document.querySelectorAll('#stickerslist tr td input.position');
  positions.forEach((element) => {
    element.style.display = 'none';
  });

  document.getElementById('theme_config')?.addEventListener('submit', () => {
    const order = [];
    positions.forEach((element) => {
      order.push(element.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    });
    document.querySelector('input[name=ds_order]').value = order.join(',');
    return true;
  });
});
