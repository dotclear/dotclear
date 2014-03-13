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

	// choose format depending of editor based on formats_by_editor defined in preferences.php
	if (formats_by_editor !== undefined) {
		var _editors = $.parseJSON(formats_by_editor);
	
		$('#user_editor').change(function() {
			if (!_editors[$(this).val()]) {return;}
			
			$('#user_post_format option').remove();
			for (var format in _editors[$(this).val()]) {
				$('#user_post_format').append('<option value="'+format+'">'+format+'</option>');
			}
		});
	}
});
