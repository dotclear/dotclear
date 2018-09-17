/*global $, dotclear */
'use strict';

dotclear.viewCommentContent = function(line, action, e) {
  action = action || 'toggle';
  if ($(line).attr('id') == undefined) {
    return;
  }

  var commentId = $(line).attr('id').substr(1);
  var tr = document.getElementById('ce' + commentId);
  var spam = (e.metaKey || $(line).hasClass('sts-junk'));

  if (!tr) {
    // Get comment content if possible
    dotclear.getCommentContent(commentId, function(content) {
      if (content) {
        // Content found
        tr = document.createElement('tr');
        tr.id = 'ce' + commentId;
        var td = document.createElement('td');
        td.colSpan = $(line).children('td').length;
        td.className = 'expand';
        tr.appendChild(td);
        $(td).append(content);
        $(line).addClass('expand');
        line.parentNode.insertBefore(tr, line.nextSibling);
      } else {
        // No content, content not found or server error
        $(line).removeClass('expand');
      }
    }, {
      clean: spam
    });
  } else {
    $(tr).toggle();
    $(line).toggleClass('expand');
  }
};

$(function() {
  $.expandContent({
    line: $('#form-comments tr:not(.line)'),
    lines: $('#form-comments tr.line'),
    callback: dotclear.viewCommentContent
  });
  $('.checkboxes-helpers').each(function() {
    dotclear.checkboxesHelpers(this, undefined, '#form-comments td input[type=checkbox]', '#form-comments #do-action');
  });
  $('#form-comments td input[type=checkbox]').enableShiftClick();
  dotclear.commentsActionsHelper();
  dotclear.condSubmit('#form-comments td input[type=checkbox]', '#form-comments #do-action');
  $('form input[type=submit][name=delete_all_spam]').click(function() {
    return window.confirm(dotclear.msg.confirm_spam_delete);
  });
});
