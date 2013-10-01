$(function() {
	$('.module-name').not('.current-theme .module-name').each(function(){
		$(this).toggleWithLegend($(this).parent().children('.module-infos'),{legend_click: true});
	});
/* Exemple for toggle on screenshot
	$('.module-sshot').not('.current-theme .module-name').each(function(){
		$(this).toggleWithLegend($(this).parent().children('.module-infos'),{
			img_on_src: '',
			img_on_alt: '',
			img_off_src: '',
			img_off_alt: '', 
			legend_click: true
		});
	});
*/
});