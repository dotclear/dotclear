function split( val ) {
	return val.split( /,\s*/ );
}
function extractLast(term) {
	return split(term).pop();
}

$(function() {
	$('#f_author')
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
				url: 'services.php',
				data: {
					'f': 'searchCommentAuthor',
					'q': extractLast(request.term)
				},
				success:function(data,status) {
					results = [];
					$(data).find('author').each(function(){
						var name = $(this).attr('name');
						results.push({label:name,value:name});
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