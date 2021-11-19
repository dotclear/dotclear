/*global $, dotclear */
'use strict';

// Get context
Object.assign(dotclear, dotclear.getData('admin.blog_pref'));

$(() => {
  $('#link-insert-cancel').on('click', () => {
    window.close();
  });

  $('#form-entries tr>td.maximal>a').on('click', function () {
    function stripBaseURL(url) {
      if (dotclear.base_url != '' && url.indexOf(dotclear.base_url) == 0) {
        return url.substr(dotclear.base_url.length);
      }

      return url;
    }

    // Get entry URL
    const main = window.opener;
    const title = stripBaseURL($(this).attr('title'));

    // Remove base scheme from beginning
    const next = title.indexOf('/');
    const href = next === -1 ? title : title.substring(next + 1);

    // Set new URL
    main.$('#static_home_url').prop('value', href);

    window.close();
  });
});
