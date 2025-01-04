/*global dotclear */
'use strict';

dotclear.viewPostContent = (line, _action = 'toggle', e = null) => {
  const target = dotclear.node(line);
  if (!target || target.getAttribute('id') === null) return;

  const postId = target.getAttribute('id').substring(1);
  const lineId = `pe${postId}`;
  let tr = document.getElementById(lineId);

  if (tr) {
    tr.style.display = tr.style.display === 'none' ? '' : 'none';
    target.classList.toggle('expand');
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
          // Set colspan of supplementary line to all cells of target line as we need only one cell in this line
          td.colSpan = target.children.length;
          td.className = 'expand';
          tr.appendChild(td);
          td.append(...dotclear.htmlToNodes(content));
          target.classList.add('expand');
          line.parentNode.insertBefore(tr, line.nextSibling);
          return;
        }
        target.classList.remove('expand');
      },
      {
        clean: e.metaKey,
      },
    );
  }
};

dotclear.ready(() => {
  // DOM ready and content loaded

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

  // Type switcher (on entry list selector)
  const switcher = document.getElementById('type');
  if (switcher)
    switcher.addEventListener('change', (event) => {
      event.currentTarget.form.submit();
    });
});
