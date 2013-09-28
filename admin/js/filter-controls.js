$(function() {
	
	$filtersform = $('#filters-form');
	$filtersform.before('<p><a id="filter-control" class="form-control" href="?" style="display:inline">'+dotclear.msg.filter_posts_list+'</a></p>')
	
	if( dotclear.msg.show_filters == 'false' ) {
		$filtersform.hide();
	} else {
		$('#filter-control')
			.addClass('open')
			.text(dotclear.msg.cancel_the_filter);
	}
	
	$('#filter-control').click(function() {
		if( $(this).hasClass('open') ) {
			if( dotclear.msg.show_filters == 'true' ) {
				return true;
			} else {
				$filtersform.hide();
				$(this).removeClass('open')
					   .text(dotclear.msg.filter_posts_list);
			}
		} else {
			$filtersform.show();
			$(this).addClass('open')
				   .text(dotclear.msg.cancel_the_filter);
		}
		return false;
	});
});