/*global $, dotclear */
'use strict';

dotclear.viewCommentContent = (line, _action = 'toggle', e = null) => {
  if ($(line).attr('id') === undefined) {
    return;
  }

  const commentId = $(line).attr('id').substring(1);
  const lineId = `ce${commentId}`;
  let tr = document.getElementById(lineId);

  // If meta key down or it's a spam then display content HTML code
  const clean = e.metaKey || $(line).hasClass('sts-junk');

  if (tr) {
    $(tr).toggle();
    $(line).toggleClass('expand');
  } else {
    // Get comment content if possible
    dotclear.getCommentContent(
      commentId,
      (content) => {
        if (content) {
          // Content found
          tr = document.createElement('tr');
          tr.id = lineId;
          const td = document.createElement('td');
          td.colSpan = $(line).children('td').length;
          td.className = 'expand';
          tr.appendChild(td);
          $(td).append(content);
          $(line).addClass('expand');
          line.parentNode.insertBefore(tr, line.nextSibling);
          return;
        }
        // No content, content not found or server error
        $(line).removeClass('expand');
      },
      {
        clean,
      },
    );
  }
};

dotclear.ready(() => {
  // DOM ready and content loaded
  dotclear.expandContent({
    line: document.querySelector('#form-comments tr:not(.line)'),
    lines: document.querySelectorAll('#form-comments tr.line'),
    callback: dotclear.viewCommentContent,
  });
  for (const elt of document.querySelectorAll('.checkboxes-helpers')) {
    dotclear.checkboxesHelpers(elt, undefined, '#form-comments td input[type=checkbox]', '#form-comments #do-action');
  }
  dotclear.enableShiftClick('#form-comments td input[type=checkbox]');
  dotclear.commentsActionsHelper();
  dotclear.condSubmit('#form-comments td input[type=checkbox]', '#form-comments #do-action');
  dotclear.responsiveCellHeaders(document.querySelector('#form-comments table'), '#form-comments table', 1);
  $('form input[type=submit][name=delete_all_spam]').on('click', () => window.confirm(dotclear.msg.confirm_spam_delete));
});
