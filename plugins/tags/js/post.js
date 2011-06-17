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

	tinymce.create('tinymce.plugins.dcTag', {
		init : function(ed, url) {
			this.editor = ed;
			
			ed.addCommand('dcTag', function() {
				var se = ed.selection;
				
				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'A')) {
					 return;
				}
				
				window.dc_tag_editor.addMeta(se.getContent());
				tinymce.execCommand('mceInsertLink', false, '#mce_temp_url#', {skip_undo : 1});
		
				elementArray = tinymce.grep(ed.dom.select("a"),function(n) {return ed.dom.getAttrib(n,'href') == '#mce_temp_url#';});
				for (i=0; i<elementArray.length; i++) {
					var node = elementArray[i];
					ed.dom.setAttrib(node,'href',ed.getParam('tag_url_pattern')+'/'+se.getContent());
					ed.dom.setAttrib(node,'title','Tag: ' + se.getContent());
				}
				ed.focus();
				ed.selection.select(node);
				ed.selection.collapse(0);
				tinymce.storeSelection();
				tinymce.execCommand('mceEndUndoLevel');
			});
			
			ed.addButton('tag', {
				title : 'dcTag.tag_desc',
				cmd : 'dcTag'
			});
			
			ed.addShortcut('ctrl+alt+t', 'dcTag.tag_desc', 'dcTag');
			
			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setDisabled('tag', co || ed.dom.getParent(n, 'A'));
			});
		},
		
		getInfo : function() {
			return {
				longname : 'Dotclear tag',
				author : 'Tomtom for dotclear',
				authorurl : 'http://dotclear.org',
				infourl : '',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});
	
	tinymce.PluginManager.add('dcTag', tinymce.plugins.dcTag);
});