/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const move = (selection) => {
    window.location = selection;
    const block = document.getElementById(selection.substring(1));
    block.open = true;
    block.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
  };

  // Hide submit buttons
  document.getElementById('gs_submit').style.display = 'none';
  document.getElementById('ls_submit').style.display = 'none';

  // Listen for selection change
  document.getElementById('gs_nav')?.addEventListener('change', (event) => move(event.target.value));
  document.getElementById('ls_nav')?.addEventListener('change', (event) => move(event.target.value));

  // Prepare mobile display for tables
  dotclear.responsiveCellHeaders(document.querySelector('table.settings'), 'table.settings', 0, true);
  for (const element of document.querySelectorAll('table.settings')) {
    element.classList.add('rch', 'rch-thead');
  }
});
