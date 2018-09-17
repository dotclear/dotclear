/*global $, dotclear */
'use strict';

dotclear.getEntryContent = function(postId, callback, options) {
  var res = '';
  var opt = $.extend({
    // Entry type (default: post)
    type: '',
    // Alert on error
    alert: true,
    // Clean content (useful for potential XSS in spam)
    clean: false,
    // Cut content after length chars (-1 to not cut)
    length: -1,
  }, options);

  // Check callback fn()
  if (!$.isFunction(callback)) {
    return;
  }

  // Get entry content
  $.get('services.php', {
      f: 'getPostById',
      id: postId,
      post_type: opt.type,
      xd_check: dotclear.nonce
    })
    .done(function(data) {
      // Response received
      var rsp = $(data).children('rsp')[0];
      if (rsp.attributes[0].value == 'ok') {
        var excerpt = $(rsp).find('post_display_excerpt').text();
        var content = $(rsp).find('post_display_content').text();
        if (excerpt || content) {
          // Clean content if requested
          if (opt.clean) {
            var text = document.createElement('textarea');
            if (excerpt) {
              text.textContent = excerpt;
              excerpt = text.innerHTML;
            }
            if (content) {
              text.textContent = content;
              content = text.innerHTML;
            }
          }
          // Compose full content
          if (!opt.clean) {
            content = (excerpt ? `${excerpt}<hr />` : '') + content;
          }
          // Cut content if requested
          if (opt.length > -1) {
            content = content.substr(0, opt.length);
          }
          if (opt.clean && content) {
            content = `<pre>${content}</pre>`;
          }
          res = content;
        }
      } else {
        if (opt.alert) {
          window.alert($(rsp).find('message').text());
        }
      }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      // No response
      window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
      if (opt.alert) {
        window.alert('Server error');
      }
    })
    .always(function() {
      // Finally
      callback(res);
    });
};

dotclear.getCommentContent = function(commentId, callback, options) {
  var res = '';
  var opt = $.extend({
    // Get comment metadata (email, site, …)
    metadata: true,
    // Show IP in metadata
    ip: true,
    // Alert on error
    alert: true,
    // Clean content (useful for potential XSS in spam)
    clean: false,
    // Cut content after length chars (-1 to not cut)
    length: -1,
  }, options);

  // Check callback fn()
  if (!$.isFunction(callback)) {
    return;
  }

  // Get comment content
  $.get('services.php', {
      f: 'getCommentById',
      id: commentId,
      xd_check: dotclear.nonce
    })
    .done(function(data) {
      // Response received
      var rsp = $(data).children('rsp')[0];
      if (rsp.attributes[0].value == 'ok') {
        var content = $(rsp).find('comment_display_content').text();
        if (content) {
          // Clean content if requested
          if (opt.clean) {
            var text = document.createElement('textarea');
            text.textContent = content;
            content = text.innerHTML;
          }
          // Cut content if requested
          if (opt.length > -1) {
            content = content.substr(0, opt.length);
          }
          if (opt.clean && content) {
            content = `<pre>${content}</pre>`;
          }
          // Get metadata (if requested)
          if (opt.metadata) {
            var comment_email = $(rsp).find('comment_email').text();
            var comment_site = $(rsp).find('comment_site').text();
            var comment_ip = $(rsp).find('comment_ip').text();
            var comment_spam_disp = $(rsp).find('comment_spam_disp').text();

            content += `<p>
              <strong>${dotclear.msg.website}</strong> ${comment_site}<br />
              <strong>${dotclear.msg.email}</strong> ${comment_email}`;
            if (opt.ip) {
              content += `<br />
                <strong>${dotclear.msg.ip_address}</strong> <a href="comments.php?ip=${comment_ip}">${comment_ip}</a>`;
            }
            content += `</p>${comment_spam_disp}`;
          }
          res = content;
        }
      } else {
        if (opt.alert) {
          window.alert($(rsp).find('message').text());
        }
      }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      // No response
      window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
      if (opt.alert) {
        window.alert('Server error');
      }
    })
    .always(function() {
      // Finally
      callback(res);
    });
};
