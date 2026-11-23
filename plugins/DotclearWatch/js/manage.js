/*global dotclear */
'use strict';

dotclear.ready(() => {
  // Details of a module: line after the module line, shown or hidden with the expand button
  const toggle = (line, action = '') => {
    const target = dotclear.node(line);
    const details = target ? document.getElementById(`${target.id}-details`) : null;
    if (!details) return;
    const show = action === '' ? details.hidden : action === 'open';
    details.hidden = !show;
    target.classList.toggle('expand', show);
  };

  const lines = document.querySelectorAll('#dcw-modules tr.dcw-line');
  for (const line of lines) {
    const details = document.getElementById(`${line.id}-details`);
    if (details) details.hidden = true;
  }
  dotclear.expandContent({
    line: document.querySelector('#dcw-modules thead tr'),
    lines,
    callback: toggle,
  });

  // Modules with alerts are open
  for (const button of document.querySelectorAll('#dcw-modules tr.dcw-line-alert .details-cmd-single')) {
    button.click();
  }
});
