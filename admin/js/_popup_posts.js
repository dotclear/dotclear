/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Type switcher (on entry list selector)
  const switcher = document.getElementById('type');
  if (switcher) {
    // Hide Ok button
    const button = document.getElementById('type-submit');
    if (button) button.style.display = 'none';

    // Watch select changes
    switcher.addEventListener('change', (event) => {
      event.currentTarget.form.submit();
    });

    // Give focus to post type field
    switcher.focus();
  }
});
