/*global dotclear */
'use strict';

/**
 * Gets the entry (post, page, …) content.
 *
 * @param      numeric     postId    The post identifier
 * @param      function    callback  The callback
 * @param      object      options   The options
 */
dotclear.getEntryContent = (postId, callback, options) => {
  // Check callback fn()
  if (typeof callback !== 'function') {
    return;
  }

  let res = '';
  const config = {
    type: '', // Entry type (default: post)
    alert: true, // Alert on error
    clean: false, // Clean content (useful for potential XSS in spam)
    length: -1, // Cut content after length chars (-1 to not cut)
  };
  const opt = { ...config, ...options };

  // Get entry content
  dotclear.jsonServicesGet(
    'getPostById',
    (data) => {
      // Response received
      let excerpt = data.post_display_excerpt;
      let content = data.post_display_content;
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
      callback(res);
    },
    {
      id: postId,
      post_type: opt.type,
    },
    (error) => {
      if (opt.alert) window.alert(error);
      callback(res);
    },
  );
};

/**
 * Gets the comment content.
 *
 * @param      numeric     commentId  The comment identifier
 * @param      function    callback   The callback
 * @param      object      options    The options
 */
dotclear.getCommentContent = (commentId, callback, options) => {
  // Check callback fn()
  if (typeof callback !== 'function') {
    return;
  }

  let res = '';
  const config = {
    metadata: true, // Get comment metadata (email, site, …)
    ip: true, // Show IP in metadata
    alert: true, // Alert on error
    clean: false, // Clean content (useful for potential XSS in spam)
    length: -1, // Cut content after length chars (-1 to not cut)
  };
  const opt = { ...config, ...options };

  // Get comment content
  dotclear.jsonServicesGet(
    'getCommentById',
    (data) => {
      // Response received
      let content = data.comment_display_content;
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
          content += `<p>
              <strong>${dotclear.msg.website}</strong> ${data.comment_site}<br />
              <strong>${dotclear.msg.email}</strong> ${data.comment_email}`;
          if (opt.ip && dotclear.data.showIp) {
            content += `<br />
                <strong>${dotclear.msg.ip_address}</strong> <a href="comments.php?ip=${data.comment_ip}">${data.comment_ip}</a>`;
          }
          content += `</p>${data.comment_spam_disp}`;
        }
        res = content;
      }
      callback(res);
    },
    {
      id: commentId,
    },
    (error) => {
      if (opt.alert) window.alert(error);
      callback(res);
    },
  );
};
