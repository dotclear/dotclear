$(function() {
	$("#filters-list").sortable({'cursor':'move'});
	$("#filters-list tr").hover(function () {
		$(this).css({'cursor':'move'});
	}, function () {
		$(this).css({'cursor':'auto'});
	});
	$('#filters-form').submit(function() {
		var order=[];
		$("#filters-list tr td input.position").each(function() {
			order.push(this.name.replace(/^f_order\[([^\]]+)\]$/,'$1'));
		});
		$("input[name=filters_order]")[0].value = order.join(',');
		return true;
	});
	$("#filters-list tr td input.position").hide();
	$("#filters-list tr td.handle").addClass('handler');
});
