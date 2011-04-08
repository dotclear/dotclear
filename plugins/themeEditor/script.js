$(function() {
	var msg = false;
	$('#file-form input[name="write"]').click(function() {
		var f = this.form;
		
		var data = {
			file_content: $(f).find('#file_content').get(0).value,
			xd_check: $(f).find('input[name="xd_check"]').get(0).value,
			write: 1
		};
		
		if (msg == false) {
			msg = $('<p style="font-weight:bold; color:red;"></p>');
			$('#file_content').parent().after(msg);
		}
		
		msg.text(dotclear.msg.saving_document);
		
		$.post(document.location.href,data,function(res,status) {
			var err = $(res).find('div.error li:first');
			if (err.length > 0) {
				msg.text(dotclear.msg.error_occurred + ' ' + err.text());
				return;
			} else {
				msg.text(dotclear.msg.document_saved);
				$('#file-chooser').empty();
				$(res).find('#file-chooser').children().appendTo('#file-chooser');
			}
		});
		
		return false;
	});
});