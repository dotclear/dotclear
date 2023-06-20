/*global $, dotclear */
'use strict';

dotclear.dbSpamsCount = (icon_spam) => {
  const params = {
    f: 'getSpamsCount',
    xd_check: dotclear.nonce,
  };
  $.get('index.php?process=Rest', params, (data) => {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      const nb = $('rsp>count', data).attr('ret');
      if (nb != dotclear.dbSpamsCount_Counter) {
        const url = `${icon_spam.attr('href')}&status=-2`;
        // First pass or counter changed
        const icon = $(`#dashboard-main #icons p a[href="${url}"]`);
        if (icon.length) {
          // Update count if exists
          const nb_label = icon.children('span.db-icon-title-spam');
          if (nb_label.length) {
            nb_label.text(nb);
          }
        } else if (nb != '') {
          // Add full element (link + counter)
          const xml = ` <a href="${url}"><span class="db-icon-title-spam">${nb}</span></a>`;
          icon_spam.after(xml);
        }
        // Store current counter
        dotclear.dbSpamsCount_Counter = nb;
      }
    }
  });
};

$(() => {
  // run counters' update on some dashboard icons
  // Spam comments
  const icon_spam = $('#dashboard-main #icons p #icon-process-comments-fav');
  if (icon_spam.length) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbSpamsCount(icon_spam);
    // Then fired every 60 seconds (1 minute)
    dotclear.dbSpamsCount_Timer = setInterval(dotclear.dbSpamsCount, 60 * 1000, icon_spam);
  }
});
