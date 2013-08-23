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

		// Hide some fields
		$('#notes-area label').toggleWithLegend($('#notes-area').children().not('label'),{
			cookie: 'dcx_post_notes',
		        hide: $('#post_notes').val() == '',
		        legend_click: true
		});
		$('#create_cat').toggleWithLegend(
			$('#create_cat').parent().children().not('#create_cat'),
			{legend_click: true} // no cookie on new category as we don't use this every day
		);
		$('#post_lang').parent().toggleWithLegend($('#post_lang'),{
		    cookie: 'dcx_post_lang',
		    legend_click: true
		});
		$('#post_password').parent().toggleWithLegend($('#post_password'),{
			cookie: 'dcx_post_password',
		    hide: $('#post_password').val() == '',
		    legend_click: true
		});
		$('#post_status').parent().toggleWithLegend($('#post_status'),{
		        cookie: 'dcx_post_status',
		    legend_click: true
		});
		$('#post_dt').parent().toggleWithLegend($('#post_dt').parent().children().not('label'),{
		    cookie: 'dcx_post_dt',
		    legend_click: true
		});
		$('#post_format').parent().toggleWithLegend($('#post_format').parent().children().not('label').add($('#post_format').parents('p').next()),{
		    cookie: 'dcx_post_format',
		    legend_click: true
		});
		$('#cat_id').parent().toggleWithLegend($('#cat_id'),{
		    cookie: 'cat_id',
		    legend_click: true
		});
		$('#post_url').parent().toggleWithLegend($('#post_url').parent().children().not('label'),{
		    cookie: 'post_url',
		    legend_click: true
		});
		// We load toolbar on excerpt only when it's ready
		$('#excerpt-area label').toggleWithLegend($('#excerpt-area').children().not('label'),{
			cookie: 'dcx_post_excerpt',
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

		// Markup validator
		var h = document.createElement('h4');
		var a = document.createElement('a');
		a.href = '#';
		a.className = 'button';
		$(a).click(function() {
			var params = {
				xd_check: dotclear.nonce,
				f: 'validatePostMarkup',
				excerpt: $('#post_excerpt').text(),
				content: $('#post_content').text(),
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
					$(p).insertAfter(h);
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
					$(div).insertAfter(h);
					$(div).backgroundFade({sColor:'#ffffff',eColor:'#FFBABA',steps:50},function() {
							$(this).backgroundFade({sColor:'#ffbaba',eColor:'#ffffff'});
					});
				}

				return false;
			});

			return false;
		});

		a.appendChild(document.createTextNode(dotclear.msg.xhtml_validator));
		h.appendChild(a);
		$(h).appendTo('#entry-content');

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
