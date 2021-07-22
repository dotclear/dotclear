/*global $, dotclear */
'use strict';

$(function () {
  // Update page/post deletion message
  Object.assign(dotclear.msg, dotclear.getData('pages_page'));

  $('#comments').on('onetabload', function () {
    $.expandContent({
      line: $('#part-comments .comments-list tr:not(.line)'),
      lines: $('#part-comments .comments-list tr.line'),
      callback: dotclear.viewCommentContent,
    });
  });
});
