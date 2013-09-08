dotclear.commentExpander = function(line) {
	var td = line.firstChild;

	var img = document.createElement('img');
	img.src = dotclear.img_plus_src;
	img.alt = dotclear.img_plus_alt;
	img.className = 'expand';
	$(img).css('cursor','pointer');
	img.line = line;
	img.onclick = function() { dotclear.viewCommentContent(this,this.line); };

	td.insertBefore(img,td.firstChild);
};

dotclear.viewCommentContent = function(img,line) {
	var commentId = line.id.substr(1);

	var tr = document.getElementById('ce'+commentId);

	if (!tr) {
		tr = document.createElement('tr');
		tr.id = 'ce'+commentId;
		var td = document.createElement('td');
		td.colSpan = 6;
		td.className = 'expand';
		tr.appendChild(td);

		img.src = dotclear.img_minus_src;
		img.alt = dotclear.img_minus_alt;

		// Get comment content
		$.get('services.php',{f:'getCommentById',id: commentId},function(data) {
			var rsp = $(data).children('rsp')[0];

			if (rsp.attributes[0].value == 'ok') {
				var comment = $(rsp).find('comment_display_content').text();

				if (comment) {
					$(td).append(comment);
					var comment_email = $(rsp).find('comment_email').text();
					var comment_site = $(rsp).find('comment_site').text();
					var comment_ip = $(rsp).find('comment_ip').text();
					var comment_spam_disp = $(rsp).find('comment_spam_disp').text();

					$(td).append('<p><strong>' + dotclear.msg.website +
					'</strong> ' + comment_site + '<br />' +
					'<strong>' + dotclear.msg.email + '</strong> ' +
					comment_email + '<br />' + comment_spam_disp + '</p>');
				}
			} else {
				alert($(rsp).find('message').text());
			}
		});

		$(line).toggleClass('expand');
		line.parentNode.insertBefore(tr,line.nextSibling);
	}
	else if (tr.style.display == 'none')
	{
		$(tr).toggle();
		$(line).toggleClass('expand');
		img.src = dotclear.img_minus_src;
		img.alt = dotclear.img_minus_alt;
	}
	else
	{
		$(tr).toggle();
		$(line).toggleClass('expand');
		img.src = dotclear.img_plus_src;
		img.alt = dotclear.img_plus_alt;
	}
};

$(function() {
	if (!document.getElementById) { return; }

	if (document.getElementById('edit-entry'))
	{
		// Get document format and prepare toolbars
		var formatField = $('#post_format').get(0);
		var last_post_format = $(formatField).val();
		$(formatField).change(function() {
			// Confirm post format change
			if(window.confirm(dotclear.msg.confirm_change_post_format_noconvert)){
				excerptTb.switchMode(this.value);
				contentTb.switchMode(this.value);
				last_post_format = $(this).val();
			}else{
				// Restore last format if change cancelled
				$(this).val(last_post_format);
			}
			
			$('.format_control > *').addClass('hide');
			if( $(this).val() == 'xhtml' ) {
				$('.control_xhtml > *').removeClass('hide');
			} else if ( $(this).val() == 'wiki' ) {
				$('.control_wiki > *').removeClass('hide');
			}
		});

		var excerptTb = new jsToolBar(document.getElementById('post_excerpt'));
		var contentTb = new jsToolBar(document.getElementById('post_content'));
		excerptTb.context = contentTb.context = 'post';
	}

	if (document.getElementById('comment_content')) {
		var commentTb = new jsToolBar(document.getElementById('comment_content'));
	}

	// Post preview
	$('#post-preview').modalWeb($(window).width()-40,$(window).height()-40);

	// Tabs events
	$('#edit-entry').onetabload(function() {
		dotclear.hideLockable();

		// Add date picker
		var post_dtPick = new datePicker($('#post_dt').get(0));
		post_dtPick.img_top = '1.5em';
		post_dtPick.draw();

		// Confirm post deletion
		$('input[name="delete"]').click(function() {
			return window.confirm(dotclear.msg.confirm_delete_post);
		});

		// Markup validator
		var v = $('<div class="format_control control_xhtml control_wiki"><p><a id="a-validator"></a></p><div/>').get(0);
		$('.control_wiki').before(v);
		var a = $('#a-validator').get(0);
		a.href = '#';
		a.className = 'button ';
		$(a).click(function() {
			
			excerpt_content = $('#post_excerpt').css('display') != 'none' ? $('#post_excerpt').val() : $('#excerpt-area iframe').contents().find('body').html();
			post_content    = $('#post_content').css('display') != 'none' ? $('#post_content').val() : $('#content-area iframe').contents().find('body').html();
			
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

				if ($(data).find('valid').text() == 1) {
					var p = document.createElement('p');
					p.id = 'markup-validator';

					if ($('#markup-validator').length > 0) {
						$('#markup-validator').remove();
					}

					$(p).addClass('message');
					$(p).text(dotclear.msg.xhtml_valid);
					$('#entry-content h3').after(p);
					$(p).backgroundFade({sColor:'#666666',eColor:'#ffcc00',steps:50},function() {
							$(this).backgroundFade({sColor:'#ffcc00',eColor:'#666666'});
					});
				} else {
					var div = document.createElement('div');
					div.id = 'markup-validator';

					if ($('#markup-validator').length > 0) {
						$('#markup-validator').remove();
					}

					$(div).addClass('error');
					$(div).html('<p><strong>' + dotclear.msg.xhtml_not_valid + '</strong></p>' + $(data).find('errors').text());
					$('#entry-content h3').after(div);
					$(div).backgroundFade({sColor:'#ffffff',eColor:'#FFBABA',steps:50},function() {
							$(this).backgroundFade({sColor:'#ffbaba',eColor:'#ffffff'});
					});
				}

				return false;
			});

			return false;
		});

		a.appendChild(document.createTextNode(dotclear.msg.xhtml_validator));
		
		$('.format_control > *').addClass('hide');
		if ( last_post_format == 'xhtml' ) {
			$('.control_xhtml > *').removeClass('hide');
		} else if ( last_post_format == 'wiki' ) {
			$('.control_wiki > *').removeClass('hide');
		}

		// Hide some fields
		$('#notes-area label').toggleWithLegend($('#notes-area').children().not('label'),{
			user_pref: 'dcx_post_notes',
			legend_click:true,
			hide: $('#post_notes').val() == ''
		});
		$('#create_cat').toggleWithLegend($('#create_cat').parent().children().not('#create_cat'),{
			// no cookie on new category as we don't use this every day
			legend_click: true
		});
		$('#post_lang').parent().children('label').toggleWithLegend($('#post_lang'),{
			user_pref: 'dcx_post_lang',
			legend_click: true
		});
		$('#post_password').parent().children('label').toggleWithLegend($('#post_password'),{
			user_pref: 'dcx_post_password',
			legend_click: true,
			hide: $('#post_password').val() == ''
		});
		$('#post_status').parent().children('label').toggleWithLegend($('#post_status'),{
			user_pref: 'dcx_post_status',
			legend_click: true
		});
		$('#post_dt').parent().children('label').toggleWithLegend($('#post_dt').parent().children().not('label'),{
			user_pref: 'dcx_post_dt',
			legend_click: true
		});
		$('#label_format').toggleWithLegend($('#label_format').parent().children().not('#label_format'),{
			user_pref: 'dcx_post_format',
			legend_click: true
		});
		$('#cat_id').parent().children('label').toggleWithLegend($('#cat_id'),{
			user_pref: 'cat_id',
			legend_click: true
		});
		$('#label_comment_tb').toggleWithLegend($('#label_comment_tb').parent().children().not('#label_comment_tb'),{
			user_pref: 'dcx_comment_tb',
			legend_click: true
		});
		$('#post_url').parent().children('label').toggleWithLegend($('#post_url').parent().children().not('label'),{
			user_pref: 'post_url',
			legend_click: true
		});
		// We load toolbar on excerpt only when it's ready
		$('#excerpt-area label').toggleWithLegend($('#excerpt-area').children().not('label'),{
			user_pref: 'dcx_post_excerpt',
			legend_click: true,
			hide: $('#post_excerpt').val() == ''
		});

		// Load toolbars
		contentTb.switchMode(formatField.value);
		excerptTb.switchMode(formatField.value);

		// Replace attachment remove links by a POST form submit
		$('a.attachment-remove').click(function() {
			this.href = '';
			var m_name = $(this).parents('ul').find('li:first>a').attr('title');
			if (window.confirm(dotclear.msg.confirm_remove_attachment.replace('%s',m_name))) {
				var f = $('#attachment-remove-hide').get(0);
				f.elements['media_id'].value = this.id.substring(11);
				f.submit();
			}
			return false;
		});

		// Check unsaved changes before XHTML conversion
		var excerpt = $('#post_excerpt').val();
		var content = $('#post_content').val();
		$('#convert-xhtml').click(function() {
			if (excerpt != $('#post_excerpt').val() || content != $('#post_content').val()) {
				return window.confirm(dotclear.msg.confirm_change_post_format);
			}
		});
	});

	$('#comments').onetabload(function() {
		$('.comments-list tr.line').each(function() {
			dotclear.commentExpander(this);
		});
		$('.checkboxes-helpers').each(function() {
			dotclear.checkboxesHelpers(this);
		});

		dotclear.commentsActionsHelper();
	});

	$('#add-comment').onetabload(function() {
		commentTb.draw('xhtml');
	});
});
