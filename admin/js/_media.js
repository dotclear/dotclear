$(function() {
	if ($('#fileupload').length==0) {
		return;
	}

	function enableButton(button) {
		button.prop('disabled',false).removeClass('disabled');
	}

	function disableButton(button) {
		button.prop('disabled',true).addClass('disabled');
	}

	function displayMessageInQueue(n) {
		var msg = '';
		if (n==1) {
			msg = dotclear.jsUpload.msg.file_in_queue;
		} else if (n>1) {
			msg = dotclear.jsUpload.msg.files_in_queue;
			msg = msg.replace(/%d/,n);
		} else {
			msg = dotclear.jsUpload.msg.no_file_in_queue;
		}
		$('.queue-message','#fileupload').html(msg);
	}

	$('.button.add').click(function(e) {
		// Use the native click() of the file input.
		$('#upfile').click();
		e.preventDefault();
	});

	$('.button.cancel', '#fileupload .fileupload-buttonbar').click(function(e) {
		$('.button.cancel','#fileupload .fileupload-buttonbar').hide();
		disableButton($('.button.start','#fileupload .fileupload-buttonbar'));
		displayMessageInQueue(0);
	});

	$('.cancel').live('click', function(e) {
		if ($('.fileupload-ctrl .files .template-upload', '#fileupload').length==0) {
			$('.button.cancel','#fileupload .fileupload-buttonbar').hide();
			disableButton($('.button.start','#fileupload .fileupload-buttonbar'));
		}
		displayMessageInQueue($('.files .template-upload','#fileupload').length);
	});

	$('.button.clean', '#fileupload').click(function(e) {
		$('.fileupload-ctrl .files .template-download', '#fileupload').slideUp(500, function() {
			$(this).remove();
		});
		$(this).hide();
		e.preventDefault();
	});

	$('#fileupload').fileupload({
		url: $('#fileupload').attr('action'),
		autoUpload: false,
		sequentialUploads: true,
		uploadTemplateId: null,
		downloadTemplateId: null,
		uploadTemplate: template_upload,
		downloadTemplate: template_download
	}).bind('fileuploadadd', function(e, data) {
		$('.button.cancel','#fileupload .fileupload-buttonbar').show();
		enableButton($('.button.start','#fileupload .fileupload-buttonbar'));
	}).bind('fileuploadadded', function(e, data) {
		displayMessageInQueue($('.files .template-upload','#fileupload').length);
	}).bind('fileuploaddone', function(e, data) {
		if (data.result.files[0].html !==undefined) {
			$('.media-list p.clear').before(data.result.files[0].html);
		}
		$('.button.clean','#fileupload').show();
	}).bind('fileuploadalways', function(e, data) {
		displayMessageInQueue($('.files .template-upload','#fileupload').length);
		if ($('.fileupload-ctrl .files .template-upload','#fileupload').length==0) {
			$('.button.cancel','#fileupload .fileupload-buttonbar').hide();
			disableButton($('.button.start','#fileupload .fileupload-buttonbar'));
		}
	});

	var $container = $('#fileupload').parent().parent();
	var $msg,label;

	if ($container.hasClass('enhanced_uploader')) {
		$msg = dotclear.msg.enhanced_uploader_disable;
		label = dotclear.jsUpload.msg.choose_files;
		$('#fileupload').fileupload({disabled:false});
		displayMessageInQueue(0);
		disableButton($('.button.start','#fileupload .fileupload-buttonbar'));
	} else {
		$msg = dotclear.msg.enhanced_uploader_activate;
		label = dotclear.jsUpload.msg.choose_file;
	}

	$('<p class="clear"><a class="enhanced-toggle" href="#">' + $msg + '</a></p>').click( function() {
		if ($container.hasClass('enhanced_uploader')) {
			$msg = dotclear.msg.enhanced_uploader_activate;
			label = dotclear.jsUpload.msg.choose_file;
			$('#upfile').attr('multiple', false);

			// when a user has clicked enhanced_uploader, and has added files
			// We must remove files in table
			$('.files .upload-file', '#fileupload').remove();
			$('.button.cancel,.button.clean','#fileupload .fileupload-buttonbar').hide();
			$('#fileupload').fileupload({disabled:true});
			$('.queue-message','#fileupload').html('').hide();
		} else {
			$msg = dotclear.msg.enhanced_uploader_disable;
			label = dotclear.jsUpload.msg.choose_files;
			$('#upfile').attr('multiple', true);
			var startButton = $('.button.start','#fileupload .fileupload-buttonbar');
			disableButton(startButton);
			startButton.show();
			$('#fileupload').fileupload({disabled:false});
			$('.queue-message','#fileupload').show();
			displayMessageInQueue(0);
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
