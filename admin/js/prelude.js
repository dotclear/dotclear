$(function() {
	if ($('#prelude').length > 0) {
		$('#prelude a')
			.addClass('hidden')
			.focus(function() {
				$('#prelude a').removeClass('hidden');
				$('#wrapper, #help-button, #collapser').addClass('with-prelude');
			});
		
		$('#prelude a[href="#help"]').click(function() {
			$('#help-button a').focus();
		});
	}
});
