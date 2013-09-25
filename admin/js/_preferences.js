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
			$(e).backgroundFade({sColor: dotclear.fadeColor.beginUserMail, eColor: dotclear.fadeColor.endUserMail, steps: 50},function() {
				$(this).backgroundFade({sColor: dotclear.fadeColor.endUserMail, eColor: dotclear.fadeColor.beginUserMail});
			});
			return false;
		}
		return true;
	});
});