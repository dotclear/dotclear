$(function() {
	if ($('#new_pwd').length == 0) {
		return;
	}
	
	var user_email = $('#user_email').val();
	
	$('#user-form').submit(function() {
		var e = this.elements['cur_pwd'];
		if (e.value != '') {
			return true;
		}
		if ($('#user_email').val() != user_email || $('#new_pwd').val() != '') {
			e.focus();
			$(e).backgroundFade({sColor:'#ffffff',eColor:'#ff9999',steps:50},function() {
				$(this).backgroundFade({sColor:'#ff9999',eColor:'#ffffff'});
			});
			return false;
		}
		return true;
	});
	$("#my-favs ul").sortable({'cursor':'move'});
	$("#my-favs ul").hover(function () {
		$(this).css({'cursor':'move'});
	}, function () {
		$(this).css({'cursor':'auto'});
	});
	$('#favs-form').submit(function() {
		var order=[];
		$("#my-favs ul li input.position").each(function() {
			order.push(this.name.replace(/^order\[([^\]]+)\]$/,'$1'));
		});
		$("input[name=favs_order]")[0].value = order.join(',');
		return true;
	});
	$("#my-favs ul li input.position").hide();

});