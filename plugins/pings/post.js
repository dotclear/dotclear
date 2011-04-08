$(function() {
	$('#edit-entry').onetabload(function() {
		if ($('p.ping-services').length > 0) {
			p = $('<p></p>');
			p.addClass('ping-services');
			a = $('<a href="#"></a>');
			a.text(dotclear.msg.check_all);
			a.click(function() {
				$('p.ping-services input[type="checkbox"]').attr('checked','checked');
				return false;
			});
			$('p.ping-services:last').after(p.append(a));
		}
		$('h3.ping-services').toggleWithLegend($('p.ping-services'),{
			cookie: 'dcx_ping_services'
		});
	});
});