/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const move = (selection) => {
    window.location = selection;
    document
      .getElementById(selection.substring(1))
      .caption?.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
  };

  // Hide submit buttons
  document.getElementById('gp_submit').style.display = 'none';
  document.getElementById('lp_submit').style.display = 'none';

  // Listen for selection change
  document.getElementById('gp_nav')?.addEventListener('change', (event) => move(event.target.value));
  document.getElementById('lp_nav')?.addEventListener('change', (event) => move(event.target.value));

  // Prepare mobile display for tables
  dotclear.responsiveCellHeaders(document.querySelector('table.prefs'), 'table.prefs', 0, true);
  document.querySelectorAll('table.prefs').forEach((element) => {
    element.classList.add('rch', 'rch-thead');
  });
});
