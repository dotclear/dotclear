/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Update page/post deletion message
  Object.assign(dotclear.msg, dotclear.getData('pages_page'));

  $('#comments').on('onetabload', () => {
    dotclear.expandContent({
      line: document.querySelector('#part-comments .comments-list tr:not(.line)'),
      lines: document.querySelectorAll('#part-comments .comments-list tr.line'),
      callback: dotclear.viewCommentContent,
    });
  });
});
