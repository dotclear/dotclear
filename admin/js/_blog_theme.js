$(function() {
	$('.module-sshot').not('.current-theme .module-sshot').each(function(){
		var bar = $('<div>').addClass('bloc-toggler');
		$(this).after(
		$(bar).toggleWithLegend($(this).parent().children('.toggle-bloc'),{
			img_on_src: dotclear.img_plus_theme_src,
			img_on_alt: dotclear.img_plus_theme_alt,
			img_off_src: dotclear.img_minus_theme_src,
			img_off_alt: dotclear.img_minus_theme_alt, 
			legend_click: true
		}));
	});
});