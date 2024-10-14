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
  const select_g = document.getElementById('gp_nav');
  const select_l = document.getElementById('lp_nav');
  select_g?.addEventListener('change', (event) => move(event.target.value));
  select_l?.addEventListener('change', (event) => move(event.target.value));

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

  // Update selector on opening a details block
  const update = (list, select) => {
    for (const item of list) {
      item.addEventListener('toggle', () => {
        if (!item.open) {
          return;
        }
        const details_id = item.id;
        select.value = `#${details_id}`;
        return;
      });
    }
  };
  const blocks_l = document.querySelectorAll('[name^="l_pref_"]');
  const blocks_g = document.querySelectorAll('[name^="g_pref_"]');
  update(blocks_l, select_l);
  update(blocks_g, select_g);
});
