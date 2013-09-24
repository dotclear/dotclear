dotclear.postExpander = function(line) {
	var td = line.firstChild;
	
	var img = document.createElement('img');
	img.src = dotclear.img_plus_src;
	img.alt = dotclear.img_plus_alt;
	img.className = 'expand';
	$(img).css('cursor','pointer');
	img.line = line;
	img.onclick = function() { dotclear.viewPostContent(this,this.line); };
	
	td.insertBefore(img,td.firstChild);
};

dotclear.postsExpander = function(line,lines) {
	var td = line.firstChild;

	var img = document.createElement('img');
	img.src = dotclear.img_plus_src;
	img.alt = dotclear.img_plus_alt;
	img.className = 'expand';
	$(img).css('cursor','pointer');
	img.lines = lines;
	img.onclick = function() { dotclear.viewPostsContent(this,this.lines); };

	td.insertBefore(img,td.firstChild);
};

dotclear.viewPostsContent = function(img,lines) {
	
	action = 'toggle';

	if (img.alt == dotclear.img_plus_alt) {
		img.src = dotclear.img_minus_src;
		img.alt = dotclear.img_minus_alt;
		action = 'open';
	} else {
		img.src = dotclear.img_plus_src;
		img.alt = dotclear.img_plus_alt;
		action = 'close';
	}
	
	lines.each(function() {
		var td = this.firstChild;
		dotclear.viewPostContent(td.firstChild,td.firstChild.line,action);
	});
};

dotclear.viewPostContent = function(img,line,action) {
	
	var action = action || 'toggle';
	var postId = line.id.substr(1);
	var tr = document.getElementById('pe'+postId);
	
	if ( !tr && ( action == 'toggle' || action == 'open' ) ) {
		tr = document.createElement('tr');
		tr.id = 'pe'+postId;
		var td = document.createElement('td');
		td.colSpan = 8;
		td.className = 'expand';
		tr.appendChild(td);
		
		img.src = dotclear.img_minus_src;
		img.alt = dotclear.img_minus_alt;
		
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
		img.src = dotclear.img_minus_src;
		img.alt = dotclear.img_minus_alt;
	}
	else if (tr && tr.style.display != 'none' && ( action == 'toggle' || action == 'close' ) )
	{
		$(tr).css('display', 'none');
		$(line).removeClass('expand');
		img.src = dotclear.img_plus_src;
		img.alt = dotclear.img_plus_alt;
	}
	
	parentTable = $(line).parents('table');
	if( parentTable.find('tr.expand').length == parentTable.find('tr.line').length ) {
		img = parentTable.find('tr:not(.line) th:first img');
		img.attr('src',dotclear.img_minus_src);
		img.attr('alt',dotclear.img_minus_alt);
	}
	
	if( parentTable.find('tr.expand').length == 0 ) {
		img = parentTable.find('tr:not(.line) th:first img');
		img.attr('src',dotclear.img_plus_src);
		img.attr('alt',dotclear.img_plus_alt);
	}
	
};

$(function() {

	$('#pageslist tr.line').prepend('<td class="expander"></td>');
	$('#form-entries tr:not(.line) th:first').attr('colspan',4);
	$('#form-entries tr:not(.line)').each(function() {
		dotclear.postsExpander(this,$('#form-entries tr.line'));
	});
	$('#pageslist tr.line').each(function() {
		dotclear.postExpander(this);
	});
	$('.checkboxes-helpers').each(function() {
		p = $('<p></p>');
		$(this).prepend(p);
		dotclear.checkboxesHelpers(p);
	});
	$('#pageslist td input[type=checkbox]').enableShiftClick();
	
	$("#pageslist tr.line td:not(.expander)").mousedown(function(){
		$('#pageslist tr.line').each(function() {
			var td = this.firstChild;
			dotclear.viewPostContent(td.firstChild,td.firstChild.line,'close');
		});
		$('#pageslist tr:not(.line)').remove();
	});
	
	$("#pageslist").sortable({
		cursor:'move',
		stop: function( event, ui ) {
			$("#pageslist tr td input.position").each(function(i) {
				$(this).val(i+1);
			});
		}
	});
	$("#pageslist tr").hover(function () {
		$(this).css({'cursor':'move'});
	}, function () {
		$(this).css({'cursor':'auto'});
	});
	$("#pageslist tr td input.position").hide();
	$("#pageslist tr td.handle").addClass('handler');
	dotclear.postsActionsHelper();
});
