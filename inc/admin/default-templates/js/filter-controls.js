$(function() {
	var c = $('#filter-control');
	c.css('display','inline');
	$('#filters-form').hide();
	c.click(function() {
		$('#filters-form').show();
		$(this).hide();
		return false;
	});
});