/*global $, dotclear, datePicker, commentTb */
'use strict';

$(function() {
  $('#comments').onetabload(function() {
    $.expandContent({
      line: $('#part-comments .comments-list tr:not(.line)'),
      lines: $('#part-comments .comments-list tr.line'),
      callback: dotclear.viewCommentContent
    });
  });
});
