/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Update page/post deletion message
  Object.assign(dotclear.msg, dotclear.getData('pages_page'));

  $.expandContent({
    line: $('#part-comments .comments-list tr:not(.line)'),
    lines: $('#part-comments .comments-list tr.line'),
    callback: dotclear.viewCommentContent,
  });
});
