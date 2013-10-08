$(function() {
	if ($('#prelude').length > 0) {
		$('#prelude a')
			.addClass('hidden')
			.focus(function() {
				$('#prelude a').removeClass('hidden');
				$('#wrapper, #help-button, #collapser').addClass('with-prelude');
			});
		
		$('body').on('click', '#prelude a[href="#help"]', function(e) {
			e.preventDefault();
			$('#help-button a').focus();
		});
	}
});
