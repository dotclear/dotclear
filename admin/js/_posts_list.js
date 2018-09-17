/*global $, dotclear */
'use strict';

dotclear.viewPostContent = function(line, action, e) {
  action = action || 'toggle';
  if ($(line).attr('id') == undefined) {
    return;
  }

  var postId = $(line).attr('id').substr(1);
  var tr = document.getElementById('pe' + postId);

  if (!tr) {
    // Get post content if possible
    dotclear.getEntryContent(postId, function(content) {
      if (content) {
        // Content found
        tr = document.createElement('tr');
        tr.id = 'pe' + postId;
        var td = document.createElement('td');
        td.colSpan = $(line).children('td').length;
        td.className = 'expand';
        tr.appendChild(td);
        $(td).append(content);
        $(line).addClass('expand');
        line.parentNode.insertBefore(tr, line.nextSibling);
      } else {
        $(line).toggleClass('expand');
      }
    }, {
      clean: (e.metaKey)
    });
  } else {
    $(tr).toggle();
    $(line).toggleClass('expand');
  }
};

$(function() {
  // Entry type switcher
  $('#type').change(function() {
    this.form.submit();
  });

  $.expandContent({
    line: $('#form-entries tr:not(.line)'),
    lines: $('#form-entries tr.line'),
    callback: dotclear.viewPostContent
  });
  $('.checkboxes-helpers').each(function() {
    dotclear.checkboxesHelpers(this, undefined, '#form-entries td input[type=checkbox]', '#form-entries #do-action');
  });
  $('#form-entries td input[type=checkbox]').enableShiftClick();
  dotclear.condSubmit('#form-entries td input[type=checkbox]', '#form-entries #do-action');
  dotclear.postsActionsHelper();
});
