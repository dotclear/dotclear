$(function() {
	// Replace remove links by a POST on hidden form
	fileRemoveAct();
	
	function fileRemoveAct() {
		$('a.media-remove').click(function() {
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
	
	if (!$.browser.opera) {
		var upldr = $('<a href="#">' + dotclear.msg.activate_enhanced_uploader + '</a>')
		.click(function() {
			candyUploadInit();
			return false;
		});
		$('#media-upload>fieldset').append($('<div></div>').append(upldr));
		
		if ($.cookie('dc_candy_upl') == 1) {
			candyUploadInit();
		}
	}
	
	function candyUploadInit()
	{
		var candy_upload_success = false;
		var candy_upload_form_url = $('#media-upload').attr('action') + '&file_sort=date-desc&d=' + $('#media-upload input[name=d]').val();
		var candy_upload_limit = $('#media-upload input[name=MAX_FILE_SIZE]').val();
		$('#media-upload').candyUpload({
			upload_url: dotclear.candyUpload.base_url + '/media.php',
			flash_movie: dotclear.candyUpload.movie_url,
			file_size_limit: candy_upload_limit + 'b',
			params: 'swfupload=1&amp;' + dotclear.candyUpload.params,
			
			callbacks: {
				createControls: function() {
					var _this = this;
					var l = $('<a href="#">' + dotclear.msg.disable_enhanced_uploader + '</a>').click(function() {
						_this.upldr.destroy();
						_this.ctrl.block.empty().remove();
						$('#media-upload').show();
						delete _this;
						$.cookie('dc_candy_upl','',{expires: -1});
						return false;
					});
					this.ctrl.disable = $('<div class="cu-disable"></div>').append(l).appendTo(this.ctrl.block);
				},
				flashReady: function() {
					this.ctrl.btn_browse.addClass('button');
					this.ctrl.block.append(this.ctrl.disable);
				},
				uploadSuccess: function(o,data) {
					if (data == 'ok') {
						candy_upload_success = true;
						this.fileMsg(o.id,this.locales.file_uploaded);
					} else {
						this.fileErrorMsg(o.id,data);
					}
					
					// uploads finished and at least one success
					if (candy_upload_success && $('div.cu-file:has(span.cu-filecancel a)',this.ctrl.files).length == 0) {
						$.cookie('dc_candy_upl','1',{expires: 30});
						$.get(candy_upload_form_url,function(data) {
							var media = $('div.media-list');
							media.after($('div.media-list',data)).remove();
							fileRemoveAct();
						});
					}
				}
			}
		});
	}
});