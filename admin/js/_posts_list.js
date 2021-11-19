/*global $, dotclear */
'use strict';

dotclear.viewPostContent = (line, action = 'toggle', e = null) => {
  if ($(line).attr('id') == undefined) {
    return;
  }

  const postId = $(line).attr('id').substr(1);
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
        } else {
          $(line).toggleClass('expand');
        }
      },
      {
        clean: e.metaKey,
      },
    );
  }
};

$(() => {
  // Entry type switcher
  $('#type').on('change', function () {
    this.form.submit();
  });

  $.expandContent({
    line: $('#form-entries tr:not(.line)'),
    lines: $('#form-entries tr.line'),
    callback: dotclear.viewPostContent,
  });
  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this, undefined, '#form-entries td input[type=checkbox]', '#form-entries #do-action');
  });
  $('#form-entries td input[type=checkbox]').enableShiftClick();
  dotclear.condSubmit('#form-entries td input[type=checkbox]', '#form-entries #do-action');
  dotclear.postsActionsHelper();
  dotclear.responsiveCellHeaders(document.querySelector('#form-entries table'), '#form-entries table', 1);
});
