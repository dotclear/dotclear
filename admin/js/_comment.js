$(function() {
	if (!document.getElementById) { return; }
	
	var tbComment = new jsToolBar(document.getElementById('comment_content'));
	tbComment.draw('xhtml');
	
	$('#comment-form input[name="delete"]').click(function() {
		return window.confirm(dotclear.msg.confirm_delete_comment);
	});
});