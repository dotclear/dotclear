$(function() {
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast(term) {
		return split(term).pop();
	}
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