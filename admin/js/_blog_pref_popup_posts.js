/*global dotclear */
'use strict';

// Get context
Object.assign(dotclear, dotclear.getData('admin.blog_pref'));

dotclear.ready(() => {
  // DOM ready and content loaded

  const cancel = document.getElementById('link-insert-cancel');
  cancel.addEventListener('click', () => {
    window.close();
  });

  const posts = document.querySelectorAll('#form-entries tr>td.maximal>a');
  posts.forEach((elt) => {
    elt.addEventListener('click', () => {
      const stripBaseURL = (url) =>
        dotclear.base_url !== '' && url.startsWith(dotclear.base_url) ? url.substr(dotclear.base_url.length) : url;

      // Get entry URL
      const main = window.opener;
      if (main) {
        const title = stripBaseURL(this.getAttribute('title'));

        // Remove base scheme from beginning
        const next = title.indexOf('/');
        const href = next === -1 ? title : title.substring(next + 1);

        // Set new URL
        main.document.getElementById('static_home_url').setAttribute('value', href);
      }

      window.close();
    });
  });
});
