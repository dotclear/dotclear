$(function() {
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast(term) {
		return split(term).pop();
	}
	$('#edit-entry').onetabload(function() {
		var tags_edit = $('#tags-edit');
		var post_id = $('#id');
		var meta_field = null;
		
		if (tags_edit.length > 0) {
			post_id = (post_id.length > 0) ? post_id.get(0).value : false;
			if (post_id == false) {
				meta_field = $('<input type="hidden" name="post_tags" />');
				meta_field.val($('#post_tags').val());
			}
			var mEdit = new metaEditor(tags_edit,meta_field,'tag');
			mEdit.displayMeta('tag',post_id);
			
			// mEdit object reference for toolBar
			window.dc_tag_editor = mEdit;
		}
		
		$('#post_meta_input')
			.bind( "keydown", function( event ) {
		if ( event.keyCode === $.ui.keyCode.TAB &&
				$( this ).data( "autocomplete" ).menu.active ) {
			event.preventDefault();
		}
	})
	.autocomplete({
		minLength: 2,
		delay: 1000,
		source: function(request,response) {
			$.ajax({
				url: mEdit.service_uri,
				data: {
					'f': 'searchMeta',
					'metaType': 'tag',
					'q': extractLast(request.term)
				},
				success:function(data) {
					results = [];
					$(data).find('meta').each(function(){
						var id = $(this).text();
						var roundpercent = $(this).attr("roundpercent");
						var count = $(this).attr("count");
						console.log(id);
						console.log(roundpercent);
						console.log(count);
						var label = id + ' (' +
							dotclear.msg.tags_autocomplete.
								replace('%p',roundpercent).
								replace('%e',count + ' ' +
								((count > 1) ?
									dotclear.msg.entries :
									dotclear.msg.entry
								)) + ')';
							console.log(label);
						results.push({label:label,value:id});
					});
					response(results);
				}
			});
		},
		search: function() {
			var term = extractLast( this.value );
			if ( term.length < 2 ) {
				return false;
			}
		},
		focus: function() {
			// prevent value inserted on focus
			return false;
		},
		select: function( event, ui ) {
			var terms = split( this.value );
			// remove the current input
			terms.pop();
			// add the selected item
			terms.push( ui.item.value );
			// add placeholder to get the comma-and-space at the end
			terms.push( "" );
			this.value = terms.join( ", " );
			return false;
		}		
	});

	});
});

// Toolbar button for tags
jsToolBar.prototype.elements.tagSpace = {type: 'space'};

jsToolBar.prototype.elements.tag = {type: 'button', title: 'Keyword', fn:{} };
jsToolBar.prototype.elements.tag.context = 'post';
jsToolBar.prototype.elements.tag.icon = 'index.php?pf=tags/img/tag-add.png';
jsToolBar.prototype.elements.tag.fn.wiki = function() {
	this.encloseSelection('','',function(str) {
		if (str == '') { window.alert(dotclear.msg.no_selection); return ''; }
		if (str.indexOf(',') != -1) {
			return str;
		} else {
			window.dc_tag_editor.addMeta(str);
			return '['+str+'|tag:'+str+']';
		}
	});
};
jsToolBar.prototype.elements.tag.fn.xhtml = function() {
	var url = this.elements.tag.url;
	this.encloseSelection('','',function(str) {
		if (str == '') { window.alert(dotclear.msg.no_selection); return ''; }
		if (str.indexOf(',') != -1) {
			return str;
		} else {
			window.dc_tag_editor.addMeta(str);
			return '<a href="'+this.stripBaseURL(url+'/'+str)+'">'+str+'</a>';
		}
	});
};
jsToolBar.prototype.elements.tag.fn.wysiwyg = function() {
	var t = this.getSelectedText();
	
	if (t == '') { window.alert(dotclear.msg.no_selection); return; }
	if (t.indexOf(',') != -1) { return; }
	
	var n = this.getSelectedNode();
	var a = document.createElement('a');
	a.href = this.stripBaseURL(this.elements.tag.url+'/'+t);
	a.appendChild(n);
	this.insertNode(a);
	window.dc_tag_editor.addMeta(t);
};