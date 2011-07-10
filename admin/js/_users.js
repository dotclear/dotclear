$(function() {
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});
	$('#form-users').submit(function() {
		var action = $(this).find('select[name="dispatch_action"]').val();
		var user_ids = new Array();
		var nb_posts = new Array();
		var i;
		var msg_cannot_delete = false;
		
		$(this).find('input[name="user_id[]"]').each(function() {
			user_ids.push(this);
		});
		$(this).find('input[name="nb_post[]"]').each(function() {
			nb_posts.push(this.value);
		});
		
		if (action == 'deleteuser') {
			for (i=0; i<user_ids.length; i++) {
				if (nb_posts[i] > 0) {
					if (user_ids[i].checked == true) {
						msg_cannot_delete = true;
						user_ids[i].checked = false;
					}
				}
			}
			if (msg_cannot_delete == true) {
				alert(dotclear.msg.cannot_delete_users);
			}
		}
		
		var selectfields = 0;
		for (i=0; i<user_ids.length; i++) {
			selectfields += user_ids[i].checked;
		}
		
		if (selectfields == 0) {
			return false;
		}
		
		if (action == 'deleteuser') {
			return window.confirm(dotclear.msg.confirm_delete_user.replace('%s',$('input[name="user_id[]"]:checked').size()));
		}
		
		return true;
	});
});