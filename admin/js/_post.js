dotclear.viewCommentContent = function(line,action) {
	var commentId = $(line).attr('id').substr(1);
	var tr = document.getElementById('ce'+commentId);

	if (!tr) {
		tr = document.createElement('tr');
		tr.id = 'ce'+commentId;
		var td = document.createElement('td');
		td.colSpan = 6;
		td.className = 'expand';
		tr.appendChild(td);

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
	}
	else
	{
		$(tr).toggle();
		$(line).toggleClass('expand');
	}
};

$(function() {
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
			user_pref: 'dcx_post_notes',
			legend_click:true,
			hide: $('#post_notes').val() == ''
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
		$('#label_cat_id').toggleWithLegend($('#label_cat_id').parent().children().not('#label_cat_id'),{
			user_pref: 'dcx_cat_id',
			legend_click: true
		});
		$('#create_cat').toggleWithLegend($('#create_cat').parent().children().not('#create_cat'),{
			// no cookie on new category as we don't use this every day
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
	});

	$('#comments').onetabload(function() {
		$.expandContent({
			lines:$('#form-comments .comments-list tr.line'),
			callback:dotclear.viewCommentContent
		});
		$('#form-comments .checkboxes-helpers').each(function() {
			dotclear.checkboxesHelpers(this);
		});

		dotclear.commentsActionsHelper();
	});

	$('#trackbacks').onetabload(function() {
		$.expandContent({
			lines:$('#form-trackbacks .comments-list tr.line'),
			callback:dotclear.viewCommentContent
		});
		$('#form-trackbacks .checkboxes-helpers').each(function() {
			dotclear.checkboxesHelpers(this);
		});

		dotclear.commentsActionsHelper();
	});

	$('#add-comment').onetabload(function() {
		commentTb.draw('xhtml');
	});
});
