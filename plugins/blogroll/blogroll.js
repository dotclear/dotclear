$(function() {
	$("#links-list").sortable({'cursor':'move'});
	$("#links-list tr").hover(function () {
		$(this).css({'cursor':'move'});
	}, function () {
		$(this).css({'cursor':'auto'});
	});
	$('#links-form').submit(function() {
		var order=[];
		$("#links-list tr td input.position").each(function() {
			order.push(this.name.replace(/^order\[([^\]]+)\]$/,'$1'));
		});
		$("input[name=links_order]")[0].value = order.join(',');
		return true;
	});
	$("#links-list tr td input.position").hide();
	$("#links-list tr td.handle").addClass('handler');
});
