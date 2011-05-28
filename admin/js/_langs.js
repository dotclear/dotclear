$(function() {
	$('table.plugins form input[type=submit][name=delete]').click(function() {
		var l_name = $(this).parents('tr.line').find('td:first').text();
		return window.confirm(dotclear.msg.confirm_delete_lang.replace('%s',l_name));
	});
});