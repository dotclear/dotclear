$(function() {
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
			mEdit.meta_url = 'plugin.php?p=tags&m=tag_posts&amp;tag=';
			mEdit.displayMeta('tag',post_id);
			
			// mEdit object reference for toolBar
			window.dc_tag_editor = mEdit;
		}
		
		$('#post_meta_input').autocomplete(mEdit.service_uri, {
			extraParams: {
				'f': 'searchMeta',
				'metaType': 'tag'
			},
			delay: 1000,
			multiple: true,
			matchSubset: false,
			matchContains: true,
			parse: function(xml) { 
				var results = [];
				$(xml).find('meta').each(function(){
					results[results.length] = {
						data: {
							"id": $(this).text(),
							"count": $(this).attr("count"),
							"percent":  $(this).attr("roundpercent")
						},
						result: $(this).text()
					}; 
				});
				return results;
			},
			formatItem: function(tag) {
				return tag.id + ' <em>(' +
				dotclear.msg.tags_autocomplete.
					replace('%p',tag.percent).
					replace('%e',tag.count + ' ' +
						(tag.count > 1 ?
						dotclear.msg.entries :
						dotclear.msg.entry)
					) +
				')</em>';
			},
			formatResult: function(tag) {
				return tag.result;
			}
		});
	});

	$('h5 .s-tags').toggleWithLegend($('.s-tags').not('label'),{
		user_pref: 'post_tags',
		legend_click: true
	});

});

// Toolbar button for tags
jsToolBar.prototype.elements.tagSpace = {
	type: 'space', 
	format:{
		wysiwyg:true,
		wiki:true,
		xhtml:true
	}
};

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