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

  // If there is a sub-hash in URL, open the according details block and set the correct value in selector
  const hash = document.location.hash.split('.');
  if (hash.length > 1) {
    const details_id = hash[1];
    const details = document.getElementById(details_id);
    if (details) {
      details.open = true;
      const select = document.getElementById(`${Array.from(details_id)[0]}s_nav`);
      if (select) select.value = `#${details_id}`;
    }
  }
});
