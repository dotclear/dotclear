dotclear.postExpander = function(line) {
	$('<a href="#"><img src="'+dotclear.img_plus_src+'" alt="'+dotclear.img_plus_alt+'"/></a>')
		.click(function(e) {
			dotclear.toggleArrow(this);
			dotclear.viewPostContent(line);
			e.preventDefault();
		})
		.prependTo($(line).children().get(0)); // first td
};

dotclear.postsExpander = function(line,lines) {
	$('<a href="#"><img src="'+dotclear.img_plus_src+'" alt="'+dotclear.img_plus_alt+'"/></a>')
		.click(function(e) {
			dotclear.toggleArrow(this);
			lines.each(function() {
				var action = dotclear.toggleArrow(this.firstChild.firstChild);
				dotclear.viewPostContent(this,action);
			});
			e.preventDefault();
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
	
	parentTable = $(line).parents('table');
	if( parentTable.find('tr.expand').length == parentTable.find('tr.line').length ) {
		img = parentTable.find('tr:not(.line) th:first img');
	}
	
	if( parentTable.find('tr.expand').length == 0 ) {
		img = parentTable.find('tr:not(.line) th:first img');
	}	
};

$(function() {
	// Entry type switcher
	$('#type').change(function() {
		this.form.submit();
	});

	$('#form-entries tr:not(.line)').each(function() {
		dotclear.postsExpander(this,$('#form-entries tr.line'));
	});
	$('#form-entries tr.line').each(function() {
		dotclear.postExpander(this);
	});
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});
	$('#form-entries td input[type=checkbox]').enableShiftClick();
	dotclear.postsActionsHelper();
});
