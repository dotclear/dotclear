/*global dotclear */
'use strict';

dotclear.viewCommentContent = (line, _action = 'toggle', e = null) => {
  const target = dotclear.node(line);
  if (!target || target.getAttribute('id') === null) return;

  const commentId = target.getAttribute('id').substring(1);
  const lineId = `ce${commentId}`;
  let tr = document.getElementById(lineId);

  // If meta key down or it's a spam then display content HTML code
  const clean = e.metaKey || target.classList.contains('sts-junk');

  if (tr) {
    tr.style.display = tr.style.display === 'none' ? '' : 'none';
    target.classList.toggleClass('expand');
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
          // Set colspan of supplementary line to all cells of target line as we need only one cell in this line
          td.colSpan = target.children.length;
          td.className = 'expand';
          tr.appendChild(td);
          td.append(...dotclear.htmlToNodes(content));
          target.classList.add('expand');
          line.parentNode.insertBefore(tr, line.nextSibling);
          return;
        }
        // No content, content not found or server error
        target.classList.remove('expand');
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

  for (const action of document.querySelectorAll('form input[type=submit][name=delete_all_spam]')) {
    action.addEventListener('click', () => window.confirm(dotclear.msg.confirm_spam_delete));
  }
});
