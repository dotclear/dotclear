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
  document.getElementById('gp_submit').style.display = 'none';
  document.getElementById('lp_submit').style.display = 'none';

  // Listen for selection change
  document.getElementById('gp_nav')?.addEventListener('change', (event) => move(event.target.value));
  document.getElementById('lp_nav')?.addEventListener('change', (event) => move(event.target.value));

  // Prepare mobile display for tables
  dotclear.responsiveCellHeaders(document.querySelector('table.prefs'), 'table.prefs', 0, true);
  for (const element of document.querySelectorAll('table.prefs')) {
    element.classList.add('rch', 'rch-thead');
  }

  // If there is a sub-hash in URL, open the according details block and set the correct value in selector
  const hash = document.location.hash.split('.');
  if (hash.length > 1) {
    const details_id = hash[1];
    const details = document.getElementById(details_id);
    if (details) {
      details.open = true;
      const select = document.getElementById(`${Array.from(details_id)[0]}p_nav`);
      if (select) select.value = `#${details_id}`;
    }
  }
});
