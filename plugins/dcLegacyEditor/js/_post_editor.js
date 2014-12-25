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
		// Markup validator
		var v = $('<div class="format_control"><p><a id="a-validator"></a></p><div/>').get(0);
		$('.format_control').before(v);
		var a = $('#a-validator').get(0);
		a.href = '#';
		a.className = 'button ';
		$(a).click(function() {

			excerpt_content = $('#post_excerpt').css('display') != 'none' ? $('#post_excerpt').val() : $('#excerpt-area iframe').contents().find('body').html();
			post_content	= $('#post_content').css('display') != 'none' ? $('#post_content').val() : $('#content-area iframe').contents().find('body').html();

			var params = {
				xd_check: dotclear.nonce,
				f: 'validatePostMarkup',
				excerpt: excerpt_content,
				content: post_content,
				format: $('#post_format').get(0).value,
				lang: $('#post_lang').get(0).value
			};

			$.post('services.php',params,function(data) {
				if ($(data).find('rsp').attr('status') != 'ok') {
					alert($(data).find('rsp message').text());
					return false;
				}

				$('.message, .success, .error, .warning-msg').remove();

				if ($(data).find('valid').text() == 1) {
					var p = document.createElement('p');
					p.id = 'markup-validator';

					$(p).addClass('success');
					$(p).text(dotclear.msg.xhtml_valid);
					$('#entry-content h3').after(p);
					$(p).backgroundFade({sColor: dotclear.fadeColor.beginValidatorMsg, eColor: dotclear.fadeColor.endValidatorMsg, steps: 50},function() {
						$(this).backgroundFade({sColor: dotclear.fadeColor.endValidatorMsg, eColor: dotclear.fadeColor.beginValidatorMsg});
					});
				} else {
					var div = document.createElement('div');
					div.id = 'markup-validator';

					$(div).addClass('error');
					$(div).html('<p><strong>' + dotclear.msg.xhtml_not_valid + '</strong></p>' + $(data).find('errors').text());
					$('#entry-content h3').after(div);
					$(div).backgroundFade({sColor: dotclear.fadeColor.beginValidatorErr,eColor: dotclear.fadeColor.endValidatorErr, steps: 50},function() {
						$(this).backgroundFade({sColor: dotclear.fadeColor.endValidatorErr, eColor: dotclear.fadeColor.beginValidatorErr});
					});
				}

				if ( $('#post_excerpt').text() != excerpt_content || $('#post_content').text() != post_content ) {
					var pn = document.createElement('p');
					$(pn).addClass('warning-msg');
					$(pn).text(dotclear.msg.warning_validate_no_save_content);
					$('#entry-content h3').after(pn);
				}

				return false;
			});

			return false;
		});

		a.appendChild(document.createTextNode(dotclear.msg.xhtml_validator));

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
