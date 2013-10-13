$(function() {
	// expand a module line
	$('table.modules.expandable tr.line').each(function(){
		$('td.module-name',this).toggleWithLegend($(this).next('.module-more'),{
			img_on_src: dotclear.img_plus_src,
			img_on_alt: dotclear.img_plus_alt,
			img_off_src: dotclear.img_minus_src,
			img_off_alt: dotclear.img_minus_alt,
			legend_click: true
		});
	});

	// confirm module deletion
	$('td.module-actions form input[type=submit][name=delete]').click(function() {
		var module_id = $('input[name=module]',$(this).parent()).val();
		return window.confirm(dotclear.msg.confirm_delete_plugin.replace('%s',module_id));
	});
});