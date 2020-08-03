/*global $, dotclear, datePicker, commentTb */
'use strict';

dotclear.viewCommentContent = function(line, action, e) {
  action = action || 'toggle';
  if ($(line).attr('id') == undefined) {
    return;
  }

  const commentId = $(line).attr('id').substr(1);
  const lineId = `ce${commentId}`;
  let tr = document.getElementById(lineId);

  // If meta key down or it's a spam then display content HTML code
  const clean = (e.metaKey || $(line).hasClass('sts-junk'));

  if (!tr) {
    // Get comment content
    dotclear.getCommentContent(commentId, function(content) {
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
        // No content, content not found or server error
        $(line).removeClass('expand');
      }
    }, {
      ip: false,
      clean: clean
    });
  } else {
    $(tr).toggle();
    $(line).toggleClass('expand');
  }
};

$(function() {
  // Post preview
  let $preview_url = $('#post-preview').attr('href');
  if ($preview_url) {

    // Make $preview_url absolute
    let $a = document.createElement('a');
    $a.href = $('#post-preview').attr('href');
    $preview_url = $a.href;

    // Check if admin and blog have same protocol (ie not mixed-content)
    if (window.location.protocol == $preview_url.substring(0, window.location.protocol.length)) {
      // Open preview in a modal iframe
      $('#post-preview').magnificPopup({
        type: 'iframe',
        iframe: {
          patterns: {
            dotclear_preview: {
              index: $preview_url,
              src: $preview_url
            }
          }
        }
      });
    } else {
      // Open preview on antother window
      $('#post-preview').on('click', function(e) {
        e.preventDefault();
        window.open($(this).attr('href'));
      });
    }
  }
  // Prevent history back if currently previewing Post (with magnificPopup
  history.pushState(null, null);
  window.addEventListener('popstate', function() {
    if (document.querySelector('.mfp-ready')) {
      // Prevent history back
      history.go(1);
      // Close current preview
      $.magnificPopup.close();
    }
  });

  // Tabs events
  $('#edit-entry').on('onetabload', function() {
    dotclear.hideLockable();

    // Add date picker
    const post_dtPick = new datePicker($('#post_dt').get(0));
    post_dtPick.img_top = '1.5em';
    post_dtPick.draw();

    // Confirm post deletion
    $('input[name="delete"]').on('click', function() {
      return window.confirm(dotclear.msg.confirm_delete_post);
    });

    // Hide some fields
    $('#notes-area label').toggleWithLegend($('#notes-area').children().not('label'), {
      user_pref: 'dcx_post_notes',
      legend_click: true,
      hide: $('#post_notes').val() == ''
    });
    $('#post_lang').parent().children('label').toggleWithLegend($('#post_lang'), {
      user_pref: 'dcx_post_lang',
      legend_click: true
    });
    $('#post_password').parent().children('label').toggleWithLegend($('#post_password').parent().children().not('label'), {
      user_pref: 'dcx_post_password',
      legend_click: true,
      hide: $('#post_password').val() == ''
    });
    $('#post_status').parent().children('label').toggleWithLegend($('#post_status'), {
      user_pref: 'dcx_post_status',
      legend_click: true
    });
    $('#post_dt').parent().children('label').toggleWithLegend($('#post_dt').parent().children().not('label'), {
      user_pref: 'dcx_post_dt',
      legend_click: true
    });
    $('#label_format').toggleWithLegend($('#label_format').parent().children().not('#label_format'), {
      user_pref: 'dcx_post_format',
      legend_click: true
    });
    $('#label_cat_id').toggleWithLegend($('#label_cat_id').parent().children().not('#label_cat_id'), {
      user_pref: 'dcx_cat_id',
      legend_click: true
    });
    $('#create_cat').toggleWithLegend($('#create_cat').parent().children().not('#create_cat'), {
      // no cookie on new category as we don't use this every day
      legend_click: true
    });
    $('#label_comment_tb').toggleWithLegend($('#label_comment_tb').parent().children().not('#label_comment_tb'), {
      user_pref: 'dcx_comment_tb',
      legend_click: true
    });
    $('#post_url').parent().children('label').toggleWithLegend($('#post_url').parent().children().not('label'), {
      user_pref: 'post_url',
      legend_click: true
    });
    // We load toolbar on excerpt only when it's ready
    $('#excerpt-area label').toggleWithLegend($('#excerpt-area').children().not('label'), {
      user_pref: 'dcx_post_excerpt',
      legend_click: true,
      hide: $('#post_excerpt').val() == ''
    });

    // Replace attachment remove links by a POST form submit
    $('a.attachment-remove').on('click', function() {
      this.href = '';
      const m_name = $(this).parents('ul').find('li:first>a').attr('title');
      if (window.confirm(dotclear.msg.confirm_remove_attachment.replace('%s', m_name))) {
        var f = $('#attachment-remove-hide').get(0);
        f.elements.media_id.value = this.id.substring(11);
        f.submit();
      }
      return false;
    });
  });

  $('#comments').on('onetabload', function() {
    $.expandContent({
      line: $('#form-comments .comments-list tr:not(.line)'),
      lines: $('#form-comments .comments-list tr.line'),
      callback: dotclear.viewCommentContent
    });
    $('#form-comments .checkboxes-helpers').each(function() {
      dotclear.checkboxesHelpers(this);
    });

    dotclear.commentsActionsHelper();
  });

  $('#trackbacks').on('onetabload', function() {
    $.expandContent({
      line: $('#form-trackbacks .comments-list tr:not(.line)'),
      lines: $('#form-trackbacks .comments-list tr.line'),
      callback: dotclear.viewCommentContent
    });
    $('#form-trackbacks .checkboxes-helpers').each(function() {
      dotclear.checkboxesHelpers(this);
    });

    dotclear.commentsActionsHelper();
  });

  $('#add-comment').on('onetabload', function() {
    commentTb.draw('xhtml');
  });
});
