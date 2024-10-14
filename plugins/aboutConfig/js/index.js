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
  const select_g = document.getElementById('gs_nav');
  const select_l = document.getElementById('ls_nav');
  select_g?.addEventListener('change', (event) => move(event.target.value));
  select_l?.addEventListener('change', (event) => move(event.target.value));

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
  const blocks_l = document.querySelectorAll('[name^="l_setting_"]');
  const blocks_g = document.querySelectorAll('[name^="g_setting_"]');
  update(blocks_l, select_l);
  update(blocks_g, select_g);
});
