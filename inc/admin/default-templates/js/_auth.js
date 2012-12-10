$(window).load(function() {
	var uid = $('input[name=user_id]');
	var upw = $('input[name=user_pwd]');
	uid.focus();
	
	if (upw.length == 0) { return; }
	
	if ($.browser.mozilla) {
		uid.keypress(processKey);
	} else {
		uid.keydown(processKey);
	}
	function processKey(evt) {
		if (evt.keyCode == 13 && upw.val() == '') {
			upw.focus();
			return false;
		}
		return true;
	};
	$.cookie('dc_admin_test_cookie',true);
	if ($.cookie('dc_admin_test_cookie')) {
		$('#cookie_help').hide();
		$.cookie('dc_admin_test_cookie', '', {'expires': -1});
	} else {
		$('#cookie_help').show();
	}
	$('#issue #more').toggleWithLegend($('#issue').children().not('#more'));
});