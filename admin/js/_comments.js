dotclear.viewCommentContent = function(line,action) {
	var action = action || 'toggle';
	var commentId = $(line).attr('id').substr(1);
	var tr = document.getElementById('ce'+commentId);

	if ( !tr && ( action == 'toggle' || action == 'open' ) ) {
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
					'<strong>' + dotclear.msg.email + '</strong> ' + comment_email + '<br />' +
					'<strong>' + dotclear.msg.ip_address +
					'</strong> <a href="comments.php?ip=' + comment_ip + '">' + comment_ip + '</a>' +
					'<br />' + comment_spam_disp + '</p>');
				}
			} else {
				alert($(rsp).find('message').text());
			}
		});

		$(line).toggleClass('expand');
		line.parentNode.insertBefore(tr,line.nextSibling);
	}
	else if (tr && tr.style.display == 'none' && ( action == 'toggle' || action == 'open' ) )
	{
		$(tr).css('display', 'table-row');
		$(line).addClass('expand');
	}
	else if (tr && tr.style.display != 'none' && ( action == 'toggle' || action == 'close' ) )
	{
		$(tr).css('display', 'none');
		$(line).removeClass('expand');
	}
};

$(function() {
	$.expandContent({
		line:$('#form-comments tr:not(.line)'),
		lines:$('#form-comments tr.line'),
		callback:dotclear.viewCommentContent
	});
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});
	$('#form-comments td input[type=checkbox]').enableShiftClick();
	dotclear.commentsActionsHelper();
	$('form input[type=submit][name=delete_all_spam]').click(function(){
		return window.confirm(dotclear.msg.confirm_spam_delete);
	});
});
