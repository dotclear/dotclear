$(function() {
	if ($('#edit-entry').length==0) {return;}
	if (dotclear.legacy_editor_context===undefined
	    || dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context]===undefined) {
		return;
	}

	if ((dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].indexOf('#post_content')!==-1)
	    && (dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].indexOf('#post_excerpt')!==-1)) {
		// Get document format and prepare toolbars
		var formatField = $('#post_format').get(0);
		var last_post_format = $(formatField).val();
		$(formatField).change(function() {
			if (this.value!='dcLegacyEditor') { return;}

			var post_format = this.value;

			// Confirm post format change
			if (window.confirm(dotclear.msg.confirm_change_post_format_noconvert)) {
				excerptTb.switchMode(post_format);
				contentTb.switchMode(post_format);
				last_post_format = $(this).val();
			} else {
				// Restore last format if change cancelled
			$(this).val(last_post_format);
			}

			$('.format_control > *').addClass('hide');
			$('.format_control:not(.control_no_'+post_format+') > *').removeClass('hide');
		});

		var excerptTb = new jsToolBar(document.getElementById('post_excerpt'));
		var contentTb = new jsToolBar(document.getElementById('post_content'));
		excerptTb.context = contentTb.context = 'post';

		$('.format_control > *').addClass('hide');
		$('.format_control:not(.control_no_'+last_post_format+') > *').removeClass('hide');
	}

	if (dotclear.legacy_editor_tags_context[dotclear.legacy_editor_context].indexOf('#comment_content')!==-1) {
		if ($('#comment_content').length>0) {
			var commentTb = new jsToolBar(document.getElementById('comment_content'));
			commentTb.draw('xhtml');
		}
	}

	$('#edit-entry').onetabload(function() {

		// Load toolbars
		if (contentTb!==undefined && excerptTb!==undefined) {
			contentTb.switchMode(formatField.value);
			excerptTb.switchMode(formatField.value);
		}

		// Check unsaved changes before XHTML conversion
		var excerpt = $('#post_excerpt').val();
		var content = $('#post_content').val();
		$('#convert-xhtml').click(function() {
			if (excerpt != $('#post_excerpt').val() || content != $('#post_content').val()) {
				return window.confirm(dotclear.msg.confirm_change_post_format);
			}
		});
	});
});
