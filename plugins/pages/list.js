dotclear.viewPostContent = function(line,action) {
	var action = action || 'toggle';
	if ($(line).attr('id') == undefined) { return; }

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
	$('#pageslist tr.line').prepend('<td class="expander"></td>');
	$('#form-entries tr:not(.line) th:first').attr('colspan',4);
	$.expandContent({
		line:$('#form-entries tr:not(.line)'),
		lines:$('#form-entries tr.line'),
		callback:dotclear.viewPostContent
	});
	$('.checkboxes-helpers').each(function() {
		p = $('<p></p>');
		$(this).prepend(p);
		dotclear.checkboxesHelpers(p,undefined,'#pageslist td input[type=checkbox]','#form-entries #do-action');
	});
	$('#pageslist td input[type=checkbox]').enableShiftClick();
	dotclear.condSubmit('#pageslist td input[type=checkbox]','#form-entries #do-action');

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

	$("form input[type=submit]").click(function() {
		$("input[type=submit]", $(this).parents("form")).removeAttr("clicked");
		$(this).attr("clicked", "true");
	})

	$('#form-entries').submit(function() {
		var action = $(this).find('select[name="action"]').val();
		var checked = false;
		if ($("input[name=reorder][clicked=true]").val()) {
			return true;
		}
		$(this).find('input[name="entries[]"]').each(function() {
			if (this.checked) {
				checked = true;
			}
		});

		if (!checked) { return false; }

		if (action == 'delete') {
			return window.confirm(dotclear.msg.confirm_delete_posts.replace('%s',$('input[name="entries[]"]:checked').size()));
		}

		return true;
	});
});
