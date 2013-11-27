$(function() {
	$('form input[type=submit][name=b_del]').click(function(){
 		return window.confirm(dotclear.msg.confirm_delete_backup);
 	});
	$('form input[type=submit][name=b_revert]').click(function(){
 		return window.confirm(dotclear.msg.confirm_revert_backup);
 	});
});
