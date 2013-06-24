$(function() {
	if ($('#fileupload').length==0) {
		return;
	}

	$('.button.add').click(function(e) {
		// Use the native click() of the file input.
		$('#upfile').click();
		e.preventDefault();
	});

	$('button.clean').click(function(e) {
		$('.fileupload-ctrl .files .upload-file', '#fileupload').slideUp(500);
		$(this).remove();
		e.preventDefault();
	});

	$('#fileupload').fileupload({
		url: $('#fileupload').attr('action'),
		autoUpload: false,
		disabled: true
	}).bind('fileuploaddone', function(e, data) {
		if (data.result.files[0].html !==undefined) {
			$('.media-list p.clear').before(data.result.files[0].html);
		}
		$('button.clean').show();
	});

	var $container = $('#fileupload').parent().parent();
	var $msg,label;

	if ($container.hasClass('enhanced_uploader')) {
		$msg = dotclear.msg.enhanced_uploader_disable;
		label = dotclear.jsUpload.msg.choose_files;
		$('#fileupload').fileupload({disabled:false});
	} else {
		$msg = dotclear.msg.enhanced_uploader_activate;
		label = dotclear.jsUpload.msg.choose_file;
	}

	$('<p class="clear"><a href="#">' + $msg + '</a></p>').click( function() {
		if ($container.hasClass('enhanced_uploader')) {
			$msg = dotclear.msg.enhanced_uploader_activate;
			label = dotclear.jsUpload.msg.choose_file;
			$('#upfile').attr('multiple', false);

			// when a user has clicked enhanced_uploader, and has added files
			// We must remove files in table
			$('.files .upload-file', '#fileupload').remove();
			$('#fileupload').fileupload({disabled:true});
		} else {
			$msg = dotclear.msg.enhanced_uploader_disable;
			label = dotclear.jsUpload.msg.choose_files;
			$('#upfile').attr('multiple', true);
			$('#fileupload').fileupload({disabled:false});
		}
		$(this).find('a').text($msg);
		$('.add-label', '#fileupload').text(label);

		$container.toggleClass('enhanced_uploader');
	}).appendTo($('#fileupload'));

	// Replace remove links by a POST on hidden form
	fileRemoveAct();

	function fileRemoveAct() {
		$('a.media-remove').live('click', function() {
			var m_name = $(this).parents('ul').find('li:first>a').text();
			if (window.confirm(dotclear.msg.confirm_delete_media.replace('%s',m_name))) {
				var f = $('#media-remove-hide').get(0);
				f.elements['remove'].value = this.href.replace(/^(.*)&remove=(.*?)(&|$)/,'$2');
				this.href = '';
				f.submit();
			}
			return false;
		});
	}
});
