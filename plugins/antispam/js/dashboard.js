/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  dotclear.antispam = {
    counter: null,
  };

  dotclear.dbSpamsCount = (icon_spam) => {
    dotclear.jsonServicesGet('getSpamsCount', (data) => {
      const nb = data.ret;
      if (nb === dotclear.antispam.counter) {
        return;
      }
      const url = `${icon_spam.getAttribute('href')}&status=-2`;
      // First pass or counter changed
      const icon = document.querySelector(`#dashboard-main #icons p a[href="${url}"]`);
      if (icon) {
        // Update count if exists
        const nb_label = icon.querySelector('span.db-icon-title-spam');
        if (nb_label) {
          nb_label.textContent = nb;
        }
      } else if (nb !== '') {
        // Add full element (link + counter)
        const xml = ` <a href="${url}"><span class="db-icon-title-spam">${nb}</span></a>`;
        icon_spam.after(...dotclear.htmlToNodes(xml));
      }
      // Store current counter
      dotclear.antispam.counter = nb;
    });
  };

  // run counters' update on some dashboard icons
  // Spam comments
  const icon_spam = document.querySelector('#dashboard-main #icons p #icon-process-comments-fav');
  if (icon_spam) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbSpamsCount(icon_spam);
    // Then fired every 60 seconds (1 minute)
    dotclear.antispam.timer = setInterval(dotclear.dbSpamsCount, 60 * 1000, icon_spam);
  }
});
