'use strict';

document.addEventListener('DOMContentLoaded', () => {
  // Show/Hide main menu
  if (document.body.clientWidth < 1024) {
    const create_name = (text) =>
      text
        .toLowerCase()
        // Remove leading and trailing spaces, and any non-alphanumeric
        // characters except for ampersands, spaces and dashes.
        .replace(/^\s+|\s+$|[^a-z0-9&\s-]/g, '')
        // Replace '&' with 'and'.
        .replace(/&/g, 'and')
        // Replaces spaces with dashes.
        .replace(/\s/g, '-')
        // Squash any duplicate dashes.
        .replace(/(-)+\1/g, '$1');

    // Set toggle class to each #sidebar h2
    const h2 = document.querySelectorAll('#sidebar div div h2');
    h2.forEach((element) => {
      element.classList.add('toggle');
      element.parentNode.classList.add('hide');
      const name = create_name(element.textContent);
      element.nextElementSibling.setAttribute('name', name);
      element.innerHTML = `<a href="#${name}" title="Reveal ${element.textContent} content">${element.innerHTML}</a>`;
      element.addEventListener('click', (e) => {
        e.preventDefault();
        element.parentNode.classList.toggle('hide');
      });
    });

    // Remove the focus from the link tag when accessed with a mouse.
    const h2_link = document.querySelectorAll('h2.toggle a');
    h2_link.forEach((element) => {
      element.addEventListener('mouseup', () => {
        const event = new Event('blur', { bubbles: true, cancelable: false });
        element.dispatchEvent(event);
      });
    });
  }
});
