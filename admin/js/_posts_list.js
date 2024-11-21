/*global $, dotclear */
'use strict';

dotclear.viewPostContent = (line, _action = 'toggle', e = null) => {
  if ($(line).attr('id') === undefined) {
    return;
  }

  const postId = $(line).attr('id').substring(1);
  const lineId = `pe${postId}`;
  let tr = document.getElementById(lineId);

  if (tr) {
    $(tr).toggle();
    $(line).toggleClass('expand');
  } else {
    // Get post content if possible
    dotclear.getEntryContent(
      postId,
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
        $(line).toggleClass('expand');
      },
      {
        clean: e.metaKey,
      },
    );
  }
};

dotclear.ready(() => {
  // DOM ready and content loaded

  // Entry type switcher
  $('#type').on('change', function () {
    this.form.submit();
  });

  dotclear.expandContent({
    line: document.querySelector('#form-entries tr:not(.line)'),
    lines: document.querySelectorAll('#form-entries tr.line'),
    callback: dotclear.viewPostContent,
  });
  for (const elt of document.querySelectorAll('.checkboxes-helpers')) {
    dotclear.checkboxesHelpers(elt, undefined, '#form-entries td input[type=checkbox]', '#form-entries #do-action');
  }
  dotclear.enableShiftClick('#form-entries td input[type=checkbox]');
  dotclear.condSubmit('#form-entries td input[type=checkbox]', '#form-entries #do-action');
  dotclear.postsActionsHelper();
  dotclear.responsiveCellHeaders(document.querySelector('#form-entries table'), '#form-entries table', 1, true);
});
