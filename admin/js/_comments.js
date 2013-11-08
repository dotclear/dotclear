dotclear.commentExpander = function(line) {
	$('<a href="#"><img src="'+dotclear.img_plus_src+'" alt="'+dotclear.img_plus_alt+'"/></a>')
		.click(function() {
			dotclear.toggleArrow(this);
			dotclear.viewCommentContent(line);
		})
		.prependTo($(line).children().get(0)); // first td
};

dotclear.commentsExpander = function(line,lines) {
	$('<a href="#"><img src="'+dotclear.img_plus_src+'" alt="'+dotclear.img_plus_alt+'"/></a>')
		.click(function() {
			dotclear.toggleArrow(this);
			lines.each(function() {
				var action = dotclear.toggleArrow(this.firstChild.firstChild);
				dotclear.viewCommentContent(this,action);
			});
		})
		.prependTo($(line).children().get(0)); // first td
};

dotclear.toggleArrow = function(link,action) {
	action = action || '';
	var img = $(link).children().get(0);
	if (action=='') {
		if (img.alt==dotclear.img_plus_alt) {
			action = 'open';
		} else {
			action = 'close';
		}
	}

	if (action=='open') {
		img.src = dotclear.img_minus_src;
		img.alt = dotclear.img_minus_alt;
	} else {
		img.src = dotclear.img_plus_src;
		img.alt = dotclear.img_plus_alt;
	}

	return action;
}

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
	$('#form-comments tr:not(.line)').each(function() {
		dotclear.commentsExpander(this,$('#form-comments tr.line'));
	});
	$('#form-comments tr.line').each(function() {
		dotclear.commentExpander(this);
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
