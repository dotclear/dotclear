$(function() {
	// expend theme info
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
		$(this).children('img').click(function(){
			$(this).parent().parent().children('.bloc-toggler').click();
		});
	});

	// confirm module deletion
	$('div.module-actions form input[type=submit][name=delete]').click(function() {
		var module_id = $('input[name=module]',$(this).parent()).val();
		return window.confirm(dotclear.msg.confirm_delete_theme.replace('%s',module_id));
	});

	// dirty short search blocker
	$('div.modules-search form input[type=submit]').click(function(){
		var mlen = $('input[name=m_search]',$(this).parent()).val();
		if (mlen.length > 2){return true;}else{return false;}
	});
});