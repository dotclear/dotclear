$(function() {
	$('#media-details-tab').onetabload(function() {
		// Add date picker
		var media_dt = document.getElementById('media_dt');
		if (media_dt == undefined) { return; }
		var post_dtPick = new datePicker(media_dt);
		post_dtPick.img_top = '1.5em';
		post_dtPick.draw();
	});
	
	// Display zip file content
	$('#file-unzip').each(function() {
		var a = document.createElement('a');
		var mediaId = $(this).find('input[name=id]').val();
		var self = $(this);
		
		a.href = '#';
		$(a).text(dotclear.msg.zip_file_content);
		self.before(a);
		$(a).wrap('<p></p>');
		
		$(a).click(function() {
			$.get('services.php',{f:'getZipMediaContent',id: mediaId},function(data) {
				var rsp = $(data).children('rsp')[0];
				
				if (rsp.attributes[0].value == 'ok') {
					var div = document.createElement('div');
					var list = document.createElement('ul');
					var expanded = false;
					
					$(div).css({
						overflow: 'auto',
						border: '1px solid #ccc',
						margin: '1em 0',
						padding: '1px 0.5em'
					});
					
					$(div).append(list);
					self.before(div);
					$(a).hide();
					$(div).before('<h3>' + dotclear.msg.zip_file_content + '</h3>');
					
					$(rsp).find('file').each(function() {
						$(list).append('<li>' + $(this).text() + '</li>');
						if ($(div).height() > 200 && !expanded) {
							$(div).css({height: '200px'});
							expanded = true;
						}
					});
				} else {
					alert($(rsp).find('message').text());
				}
			});
			return false;
		});
	});
	
	// Confirm for inflating in current directory
	$('#file-unzip').submit(function() {
		if ($(this).find('#inflate_mode').val() == 'current') {
			return window.confirm(dotclear.msg.confirm_extract_current);
		}
		return true;
	});

	// Confirm for deleting current medoa
	$('#delete-form input[name="delete"]').click(function() {
		return window.confirm(dotclear.msg.confirm_delete_media);
	});

});