$(function() {
	var tag_field = $('#new_tags');
	
	tag_field.after('<div id="tags_list"></div>');
	tag_field.hide();
	
	var target = $('#tags_list');	
	var mEdit = new metaEditor(target,tag_field,'tag');
	
	mEdit.meta_dialog = $('<input type="text" />');
	mEdit.meta_dialog.attr('title',mEdit.text_add_meta.replace(/%s/,mEdit.meta_type));
	mEdit.meta_dialog.attr('id','post_meta_input');
	mEdit.meta_dialog.css('width','90%');
	
	mEdit.addMetaDialog();
	
	$('input[name="save_tags"]').click(function() {
		tag_field.val($('#post_meta_input').val());
	});
	
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