/*
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dcRevisions, a plugin for Dotclear.
#
# Copyright (c) 2010 Tomtom and contributors
# http://blog.zenstyle.fr/
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------
*/
dotclear.revisionExpander = function() {
	$('#revisions-list tr.line').each(function() {
		var img = document.createElement('img');
		img.src = dotclear.img_plus_src;
		img.alt = dotclear.img_plus_alt;
		img.className = 'expand';
		$(img).css('cursor','pointer');
		img.line = this;
		img.onclick = function() {
			dotclear.viewRevisionContent(this,this.line);
		};
		$(this).find('td.rid').prepend(img);
	});
};

dotclear.viewRevisionContent = function(img,line) {
	var revisionId = line.id.substr(1);
	var postId = $("#id").val();
	var tr = document.getElementById('re' + revisionId);
	if (!tr ){
		tr = document.createElement('tr');
		tr.id = 're' + revisionId;
		var td = document.createElement('td');
		td.colSpan = 5;
		td.className = 'expand';
		tr.appendChild(td);
		img.src = dotclear.img_minus_src;
		img.alt = dotclear.img_minus_alt;
		$.get(
			'services.php',{
				f: 'getPatch',
				pid: postId,
				rid: revisionId
			},
			function(data){
				var rsp = $(data).children('rsp')[0];
				if(rsp.attributes[0].value == 'ok'){
					var excerpt_nodes = $(rsp).find('post_excerpt_xhtml').children();
					var content_nodes = $(rsp).find('post_content_xhtml').children();
					if (excerpt_nodes.size() == 0 && content_nodes.size() == 0) {
						$(td).append('<strong>' + dotclear.msg.content_identical + '</strong>');
					}
					else {
						var excerpt = $(rsp).find('post_excerpt_xhtml');
						var content = $(rsp).find('post_content_xhtml');
						var table = '<table class="preview-rev">';
						table += dotclear.viewRevisionRender(excerpt_nodes,dotclear.msg.excerpt,revisionId);
						table += dotclear.viewRevisionRender(content_nodes,dotclear.msg.content,revisionId);
						table += '</table>';
						$(td).append(table);
					}
				}
				else {
					alert($(rsp).find('message').text());
				}
			}
		);
		$(line).toggleClass('expand');
		line.parentNode.insertBefore(tr,line.nextSibling);
	}
	else if (tr.style.display=='none') {
		$(tr).toggle();
		$(line).toggleClass('expand');
		img.src = dotclear.img_minus_src;
		img.alt = dotclear.img_minus_alt
	}
	else {
		$(tr).toggle();
		$(line).toggleClass('expand');
		img.src = dotclear.img_plus_src;
		img.alt = dotclear.img_plus_alt;
	}
};

dotclear.viewRevisionRender = function(nodes,title,revisionId){
	var res = lines = previous = '';
		
	nodes.each(function(k) {
		var name = this.nodeName;
		var content = $(this).text();
		
		var ol = $(this).attr('oline') != undefined ? $(this).attr('oline') : '';
		var nl = $(this).attr('nline') != undefined ? $(this).attr('nline') : '';
		
		if (name == 'skip') {
			ol = nl = '&hellip;';
		}
		
		var class = '';
		
		if (name == 'skip') {
			class = ' skip';
		}
		if (name == 'context') {
			class = ' context';
		}
		if (name == 'insert') {
			class = ' insert';
		}
		if (name == 'delete') {
			class = ' delete';
		}
		
		if (name != previous && (previous == '' || previous == 'context')) {
			class += ' first';
		}
		var next = nodes.size() > k+1 ? nodes.get(k+1).nodeName : '';
		if (name != next && next != 'insert' && next != 'delete') {
			class += ' last';
		}
		
		previous = name;
		
		lines += '<tr><td class="minimal col-line">'+ol+
		'</td><td class="minimal col-line">'+nl+
		'</td><td class="'+class+'">'+content+
		'</td></tr>';
	});
	
	if (lines != '') {
		res = '<thead><tr class="rev-header"><th colspan="3">'+title+'</th></tr>'+
		'<tr class="rev-number"><th class="minimal nowrap">'+dotclear.msg.current+
		'</th><th class="minimal nowrap">r'+revisionId+
		'</th><th class="maximal"></th></tr></thead><tbody>'+
		lines + '</tbody>';
	}
	
	return res;
};

$(function() {
	$('#edit-entry').onetabload(function() {
		$('#revisions-area label').toggleWithLegend(
			$('#revisions-area').children().not('label'),{
				cookie:'dcx_post_revisions',
				fn:dotclear.revisionExpander()
			}
		);
		$('#revisions-list tr.line a.patch').click(function() {
			return window.confirm(dotclear.msg.confirm_apply_patch);
		});
	});
});