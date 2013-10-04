(function($) {
	$.fn.enhancedUploader = function() {
		return this.each(function() {
			var me = $(this);

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
				$('.queue-message',me).html(msg);
			}
			
			$('.button.choose_files').click(function(e) {
				// Use the native click() of the file input.
				$('#upfile').click();
				e.preventDefault();
			});
			
			$('.button.cancel', '#fileupload .fileupload-buttonbar').click(function(e) {
				$('.button.cancel','#fileupload .fileupload-buttonbar').hide();
				disableButton($('.button.start','#fileupload .fileupload-buttonbar'));
				displayMessageInQueue(0);
			});
			
			$(me).on('click','.cancel',function(e) {
				if ($('.fileupload-ctrl .files .template-upload', me).length==0) {
					$('.button.cancel','#fileupload .fileupload-buttonbar').hide();
					disableButton($('.button.start','#fileupload .fileupload-buttonbar'));
				}
				displayMessageInQueue($('.files .template-upload',me).length);
			});
			
			$('.button.clean', me).click(function(e) {
				$('.fileupload-ctrl .files .template-download', me).slideUp(500, function() {
					$(this).remove();
				});
				$(this).hide();
				e.preventDefault();
			});
			
			$(me).fileupload({
				url: $(me).attr('action'),
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
				displayMessageInQueue($('.files .template-upload',me).length);
			}).bind('fileuploaddone', function(e, data) {
				if (data.result.files[0].html !==undefined) {
					$('.media-list .files-group').append(data.result.files[0].html);
					$('#form-medias .hide').removeClass('hide');
				}
				$('.button.clean',me).show();
			}).bind('fileuploadalways', function(e, data) {
				displayMessageInQueue($('.files .template-upload',me).length);
				if ($('.fileupload-ctrl .files .template-upload',me).length==0) {
					$('.button.cancel','#fileupload .fileupload-buttonbar').hide();
					disableButton($('.button.start','#fileupload .fileupload-buttonbar'));
				}
			});
			
			var $container = $(me).parent();
			var $msg,label;

			if ($container.hasClass('enhanced_uploader')) {
				$msg = dotclear.msg.enhanced_uploader_disable;
				label = dotclear.jsUpload.msg.choose_files;
				$(me).fileupload({disabled:false});
				displayMessageInQueue(0);
				disableButton($('.button.start','#fileupload .fileupload-buttonbar'));
			} else {
				$msg = dotclear.msg.enhanced_uploader_activate;
				label = dotclear.jsUpload.msg.choose_file;
			}

			$('<p class="clear"><a class="enhanced-toggle" href="#">' + $msg + '</a></p>').click(function() {
				if ($container.hasClass('enhanced_uploader')) {
					$msg = dotclear.msg.enhanced_uploader_activate;
					label = dotclear.jsUpload.msg.choose_file;
					$('#upfile').attr('multiple', false);
					enableButton($('.button.start','#fileupload .fileupload-buttonbar'));
					
					// when a user has clicked enhanced_uploader, and has added files
					// We must remove files in table
					$('.files .upload-file', me).remove();
					$('.button.cancel,.button.clean','#fileupload .fileupload-buttonbar').hide();
					$(me).fileupload({disabled:true});
					$('.queue-message',me).html('').hide();
				} else {
					$msg = dotclear.msg.enhanced_uploader_disable;
					label = dotclear.jsUpload.msg.choose_files;
					$('#upfile').attr('multiple', true);
					var startButton = $('.button.start','#fileupload .fileupload-buttonbar');
					disableButton(startButton);
					startButton.show();
					$(me).fileupload({disabled:false});
					$('.queue-message',me).show();
					displayMessageInQueue(0);
				}
				$(this).find('a').text($msg);
				$('.add-label', me).text(label);
				
				$container.toggleClass('enhanced_uploader');
			}).appendTo(me);
		});
	};
})(jQuery);


$(function() {
	$('#fileupload').enhancedUploader();

	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});

	$('#form-medias').submit(function() {
		var count_checked = $('input[name="medias[]"]:checked', $(this)).length;
		if (count_checked==0) {
			return false;
		}

		return window.confirm(dotclear.msg.confirm_delete_medias.replace('%d',count_checked));
	});

	// Replace remove links by a POST on hidden form
	fileRemoveAct();

	function fileRemoveAct() {
		$('body').on('click','a.media-remove',function() {
			var m_name = $(this).parents('.media-item').find('a.media-link').text();
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
