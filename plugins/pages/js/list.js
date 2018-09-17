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
      type: 'page',
      clean: (e.metaKey)
    });
  } else {
    $(tr).toggle();
    $(line).toggleClass('expand');
  }
};

$(function() {
  $('#pageslist tr.line').prepend('<td class="expander"></td>');
  $('#form-entries tr:not(.line) th:first').attr('colspan', 4);
  $.expandContent({
    line: $('#form-entries tr:not(.line)'),
    lines: $('#form-entries tr.line'),
    callback: dotclear.viewPostContent
  });
  $('.checkboxes-helpers').each(function() {
    var p = $('<p></p>');
    $(this).prepend(p);
    dotclear.checkboxesHelpers(p, undefined, '#pageslist td input[type=checkbox]', '#form-entries #do-action');
  });
  $('#pageslist td input[type=checkbox]').enableShiftClick();
  dotclear.condSubmit('#pageslist td input[type=checkbox]', '#form-entries #do-action');

  $('#pageslist tr.line td:not(.expander)').mousedown(function() {
    $('#pageslist tr.line').each(function() {
      var td = this.firstChild;
      dotclear.viewPostContent(td.firstChild, td.firstChild.line, 'close');
    });
    $('#pageslist tr:not(.line)').remove();
  });

  $('#pageslist').sortable({
    cursor: 'move',
    stop: function() {
      $('#pageslist tr td input.position').each(function(i) {
        $(this).val(i + 1);
      });
    }
  });
  $('#pageslist tr').hover(function() {
    $(this).css({
      'cursor': 'move'
    });
  }, function() {
    $(this).css({
      'cursor': 'auto'
    });
  });
  $('#pageslist tr td input.position').hide();
  $('#pageslist tr td.handle').addClass('handler');

  $('form input[type=submit]').click(function() {
    $('input[type=submit]', $(this).parents('form')).removeAttr('clicked');
    $(this).attr('clicked', 'true');
  });

  $('#form-entries').submit(function() {
    var action = $(this).find('select[name="action"]').val();
    var checked = false;
    if ($('input[name="reorder"][clicked=true]').val()) {
      return true;
    }
    $(this).find('input[name="entries[]"]').each(function() {
      if (this.checked) {
        checked = true;
      }
    });

    if (!checked) {
      return false;
    }

    if (action == 'delete') {
      return window.confirm(dotclear.msg.confirm_delete_posts.replace('%s', $('input[name="entries[]"]:checked').size()));
    }

    return true;
  });
});
