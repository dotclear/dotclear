'use strict';
dotclear.ready(() => {
  if (document.body.clientWidth >= 1024) {
    return;
  }

  const h2 = document.querySelectorAll('#sidebar div div h2, #blogcustom div h2');

  // Every div including a h2 will become a details and its h2 will be the summary
  for (const element of h2) {
    const parent = element.parentNode;

    const details = document.createElement('details');
    const summary = document.createElement('summary');

    details.classList.add('widget-mini');
    if (parent.id) details.id = parent.id;

    // Copy all div children in details
    for (const child of parent.childNodes) {
      details.appendChild(child.cloneNode(true));
    }

    // Replace 1st h2 by a summary
    const title = details.querySelector('h2');
    summary.textContent = element.textContent;
    title.replaceWith(summary);

    // Replace the div by the details
    parent.replaceWith(details);
  }
});
