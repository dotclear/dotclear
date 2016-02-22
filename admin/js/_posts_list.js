dotclear.viewPostContent = function(line,action) {
	var action = action || 'toggle';
	var postId = $(line).attr('id').substr(1);
	var tr = document.getElementById('pe'+postId);

	if ( !tr && ( action == 'toggle' || action == 'open' ) ) {
		tr = document.createElement('tr');
		tr.id = 'pe'+postId;
		var td = document.createElement('td');
		td.colSpan = 8;
		td.className = 'expand';
		tr.appendChild(td);

		// Get post content
		$.get('services.php',{f:'getPostById', id: postId, post_type: ''},function(data) {
			var rsp = $(data).children('rsp')[0];

			if (rsp.attributes[0].value == 'ok') {
				var post = $(rsp).find('post_display_content').text();
				var post_excerpt = $(rsp).find('post_display_excerpt').text();
				var res = '';

				if (post) {
					if (post_excerpt) {
						res += post_excerpt + '<hr />';
					}
					res += post;
					$(td).append(res);
				}
			} else {
				alert($(rsp).find('message').text());
			}
		});

		$(line).addClass('expand');
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
	// Entry type switcher
	$('#type').change(function() {
		this.form.submit();
	});

	$.expandContent({
		line:$('#form-entries tr:not(.line)'),
		lines:$('#form-entries tr.line'),
		callback:dotclear.viewPostContent
	});
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this,undefined,'#form-entries td input[type=checkbox]','#form-entries #do-action');
	});
	$('#form-entries td input[type=checkbox]').enableShiftClick();
	dotclear.condSubmit('#form-entries td input[type=checkbox]','#form-entries #do-action');
	dotclear.postsActionsHelper();
});
