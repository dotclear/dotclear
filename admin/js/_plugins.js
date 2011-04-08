$(function() {
	$('table.plugins form input[type=submit][name=delete]').click(function() {
		var p_name = $('input[name=plugin_id]',$(this).parent()).val();
		return window.confirm(dotclear.msg.confirm_delete_plugin.replace('%s',p_name));
	});
});