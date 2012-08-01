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
});