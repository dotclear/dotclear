$(function() {
	$("#menuitemslist").sortable({'cursor':'move'});
	$("#menuitemslist tr").hover(function () {
		$(this).css({'cursor':'move'});
	}, function () {
		$(this).css({'cursor':'auto'});
	});
	$('#menuitems').submit(function() {
		var order=[];
		$("#menuitemslist tr td input.position").each(function() {
			order.push(this.name.replace(/^order\[([^\]]+)\]$/,'$1'));
		});
		$("input[name=im_order]")[0].value = order.join(',');
		return true;
	});
	$("#menuitemslist tr td input.position").hide();
	$("#menuitemslist tr td.handle").addClass('handler');
});
