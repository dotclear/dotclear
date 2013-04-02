$(function() {
	$('#toggle-filters').click(function(e) {
		$('#filters').toggleClass('hidden');
		$('#toggle-filters').toggleClass('opened');
	});
});