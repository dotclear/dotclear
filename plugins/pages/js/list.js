/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('pages_list'));

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
        type: 'page',
        clean: e.metaKey,
      },
    );
  }
};

dotclear.ready(() => {
  // DOM ready and content loaded

  $('#pageslist tr.line').prepend('<td class="expander"></td>');
  $('#form-entries tr:not(.line) th:first').attr('colspan', 4);
  dotclear.expandContent({
    line: document.querySelector('#form-entries tr:not(.line)'),
    lines: document.querySelectorAll('#form-entries tr.line'),
    callback: dotclear.viewPostContent,
  });

  for (const elt of document.querySelectorAll('.checkboxes-helpers')) {
    dotclear.checkboxesHelpers(elt, undefined, '#pageslist td input[type=checkbox]', '#form-entries #do-action');
  }
  dotclear.enableShiftClick('#pageslist td input[type=checkbox]');
  dotclear.condSubmit('#pageslist td input[type=checkbox]', '#form-entries #do-action');
  dotclear.responsiveCellHeaders(document.querySelector('#form-entries table'), '#form-entries table', 3, true);

  $('#pageslist tr.line td:not(.expander)').on('mousedown', () => {
    $('#pageslist tr.line').each(function () {
      const td = this.firstChild;
      dotclear.viewPostContent(td.firstChild, td.firstChild.line, 'close');
    });
    $('#pageslist tr:not(.line)').remove();
  });

  if ($('#pageslist').css('display') !== 'block') {
    $('#pageslist').sortable({
      stop() {
        $('#pageslist tr td input.position').each(function (i) {
          $(this).val(i + 1);
        });
      },
    });
    $('#pageslist tr td input.position').hide();
    $('#pageslist tr td.handle').addClass('handler');
  }

  $('form input[type=submit]').on('click', function () {
    $('input[type=submit]', $(this).parents('form')).removeAttr('clicked');
    $(this).attr('clicked', 'true');
  });

  $('#form-entries').on('submit', function () {
    const action = $(this).find('select[name="action"]').val();
    let checked = false;
    if ($('input[name="reorder"][clicked=true]').val()) {
      return true;
    }
    $(this)
      .find('input[name="entries[]"]')
      .each(function () {
        if (this.checked) {
          checked = true;
        }
      });

    if (!checked) {
      return false;
    }

    if (action === 'delete') {
      return window.confirm(dotclear.msg.confirm_delete_posts.replace('%s', $('input[name="entries[]"]:checked').length));
    }

    return true;
  });
});
