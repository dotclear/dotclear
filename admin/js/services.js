/*global $, dotclear */
'use strict';

/**
 * Gets the entry (post, page, …) content.
 *
 * @param      numeric     postId    The post identifier
 * @param      function    callback  The callback
 * @param      object      options   The options
 */
dotclear.getEntryContent = (postId, callback, options) => {
  let res = '';
  const opt = $.extend(
    {
      // Entry type (default: post)
      type: '',
      // Alert on error
      alert: true,
      // Clean content (useful for potential XSS in spam)
      clean: false,
      // Cut content after length chars (-1 to not cut)
      length: -1,
    },
    options
  );

  // Check callback fn()
  if (typeof callback !== 'function') {
    return;
  }

  // Get entry content
  $.get('services.php', {
    f: 'getPostById',
    id: postId,
    post_type: opt.type,
    xd_check: dotclear.nonce,
  })
    .done((data) => {
      // Response received
      const rsp = $(data).children('rsp')[0];
      if (rsp.attributes[0].value == 'ok') {
        let excerpt = $(rsp).find('post_display_excerpt').text();
        let content = $(rsp).find('post_display_content').text();
        if (excerpt || content) {
          // Clean content if requested
          if (opt.clean) {
            const text = document.createElement('textarea');
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
            content = dotclear.trimHtml(content, { limit: opt.length }).html;
          }
          if (opt.clean && content) {
            content = `<pre>${content}</pre>`;
          }
          res = content;
        }
      } else if (opt.alert) {
        window.alert($(rsp).find('message').text());
      }
    })
    .fail((jqXHR, textStatus, errorThrown) => {
      // No response
      window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
      if (opt.alert) {
        window.alert('Server error');
      }
    })
    .always(() => {
      // Finally
      callback(res);
    });
};

/**
 * Gets the comment content.
 *
 * @param      numeric     commentId  The comment identifier
 * @param      function    callback   The callback
 * @param      object      options    The options
 */
dotclear.getCommentContent = (commentId, callback, options) => {
  let res = '';
  const opt = $.extend(
    {
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
    },
    options
  );

  // Check callback fn()
  if (typeof callback !== 'function') {
    return;
  }

  // Get comment content
  $.get('services.php', {
    f: 'getCommentById',
    id: commentId,
    xd_check: dotclear.nonce,
  })
    .done((data) => {
      // Response received
      const rsp = $(data).children('rsp')[0];
      if (rsp.attributes[0].value == 'ok') {
        let content = $(rsp).find('comment_display_content').text();
        if (content) {
          // Clean content if requested
          if (opt.clean) {
            const text = document.createElement('textarea');
            text.textContent = content;
            content = text.innerHTML;
          }
          // Cut content if requested
          if (opt.length > -1) {
            content = dotclear.trimHtml(content, { limit: opt.length }).html;
          }
          if (opt.clean && content) {
            content = `<pre>${content}</pre>`;
          }
          // Get metadata (if requested)
          if (opt.metadata) {
            const comment_email = $(rsp).find('comment_email').text();
            const comment_site = $(rsp).find('comment_site').text();
            const comment_ip = $(rsp).find('comment_ip').text();
            const comment_spam_disp = $(rsp).find('comment_spam_disp').text();

            content += `<p>
              <strong>${dotclear.msg.website}</strong> ${comment_site}<br />
              <strong>${dotclear.msg.email}</strong> ${comment_email}`;
            if (opt.ip && dotclear.data.showIp) {
              content += `<br />
                <strong>${dotclear.msg.ip_address}</strong> <a href="comments.php?ip=${comment_ip}">${comment_ip}</a>`;
            }
            content += `</p>${comment_spam_disp}`;
          }
          res = content;
        }
      } else if (opt.alert) {
        window.alert($(rsp).find('message').text());
      }
    })
    .fail((jqXHR, textStatus, errorThrown) => {
      // No response
      window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
      if (opt.alert) {
        window.alert('Server error');
      }
    })
    .always(() => {
      // Finally
      callback(res);
    });
};
