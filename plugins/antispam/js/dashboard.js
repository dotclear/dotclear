/*global $, dotclear */
'use strict';

dotclear.dbSpamsCount = function() {
  var params = {
    f: 'getSpamsCount',
    xd_check: dotclear.nonce,
  };
  $.get('services.php', params, function(data) {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      var nb = $('rsp>count', data).attr('ret');
      if (nb != dotclear.dbSpamsCount_Counter) {
        // First pass or counter changed
        var icon = $('#dashboard-main #icons p a[href="comments.php?status=-2"]');
        if (icon.length) {
          // Update count if exists
          var nb_label = icon.children('span.db-icon-title-spam');
          if (nb_label.length) {
            nb_label.text(nb);
          }
        }
        // Store current counter
        dotclear.dbSpamsCount_Counter = nb;
      }
    }
  });
};

$(function() {
  // run counters' update on some dashboard icons
  // Spam comments
  var icon_spam = $('#dashboard-main #icons p a[href="comments.php?status=-2"]');
  if (icon_spam.length) {
    // Icon exists on dashboard
    // First pass
    dotclear.dbSpamsCount();
    // Then fired every 60 seconds (1 minute)
    dotclear.dbSpamsCount_Timer = setInterval(dotclear.dbSpamsCount, 60 * 1000);
  }
});
