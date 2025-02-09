/*global $, dotclear, jsToolBar */
'use strict';

// Get context
Object.assign(dotclear, dotclear.getData('legacy_editor_ctx'));

dotclear.ready(() => {
  // DOM ready and content loaded

  if ($('#edit-entry').length === 0) {
    return;
  }

  if (
    dotclear.legacy_editor_context === undefined ||
    dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context] === undefined
  ) {
    return;
  }

  // To be reviewed!
  let formatField;
  let excerptTb;
  let contentTb;

  if (
    dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].includes('#post_excerpt') ||
    dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].includes('#post_content')
  ) {
    // Get document format and prepare toolbars
    formatField = $('#post_format').get(0);
    let last_post_format = $(formatField).val();
    $(formatField).on('change', function () {
      if (this.value !== 'dcLegacyEditor') {
        return;
      }

      const post_format = this.value;

      // Confirm post format change
      if (window.confirm(dotclear.msg.confirm_change_post_format_noconvert)) {
        if (excerptTb !== undefined) {
          excerptTb.switchMode(post_format);
        }
        if (contentTb !== undefined) {
          contentTb.switchMode(post_format);
        }
        last_post_format = $(this).val();
      } else {
        // Restore last format if change cancelled
        $(this).val(last_post_format);
      }

      $('.format_control > *').addClass('hide');
      $(`.format_control:not(.control_no_${post_format}) > *`).removeClass('hide');
    });

    if (dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].includes('#post_excerpt')) {
      excerptTb = new jsToolBar(document.getElementById('post_excerpt'));
      excerptTb.context = 'post';
    }
    if (dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].includes('#post_content')) {
      contentTb = new jsToolBar(document.getElementById('post_content'));
      contentTb.context = 'post';
    }

    $('.format_control > *').addClass('hide');
    $(`.format_control:not(.control_no_${last_post_format}) > *`).removeClass('hide');
  }

  if (
    dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].includes('#comment_content') &&
    $('#comment_content').length > 0
  ) {
    dotclear.commentTb = new jsToolBar(document.getElementById('comment_content'));
    dotclear.commentTb.draw('xhtml');
  }

  document.getElementById('comments')?.addEventListener('onetabload', () => {
    // Remove required attribut from #comment_content as textarea might be not more focusable
    if (dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].includes('#comment_content')) {
      $('#comment_content')[0].removeAttribute('required');
    }
  });

  document.getElementById('edit-entry')?.addEventListener('onetabload', () => {
    // Remove required attribut from #post_content in HTML mode as textarea is not more focusable
    if (
      formatField !== undefined &&
      formatField.value === 'xhtml' &&
      dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].includes('#post_content')
    ) {
      $('#post_content')[0].removeAttribute('required');
    }

    // Load toolbars
    if (excerptTb !== undefined) {
      excerptTb.switchMode(formatField.value);
    }
    if (contentTb !== undefined) {
      contentTb.switchMode(formatField.value);
    }

    // Check unsaved changes before HTML conversion
    const excerpt = $('#post_excerpt').val();
    const content = $('#post_content').val();
    $('#convert-xhtml').on('click', () => {
      if (excerpt !== $('#post_excerpt').val() || content !== $('#post_content').val()) {
        return window.confirm(dotclear.msg.confirm_change_post_format);
      }
    });
  });
});
