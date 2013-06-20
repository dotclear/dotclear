$(function() {
    var jqXHR = null;
    $('#fileupload').fileupload({
	url: $('#fileupload').attr('action'),
	autoUpload: false,
	disabled: true
    }).bind('fileuploaddone', function(e, data) {
	if (data.result.files[0].html !==undefined) {
	    $('.media-list').append(data.result.files[0].html);
	}
    });

    if (!$.browser.opera) {
	var $container = $('#fileupload').parent().parent();
	var $msg;

	if ($container.hasClass('enhanced_uploader')) {
	    $msg = dotclear.msg.enhanced_uploader_disable;
	    $('#fileupload').fileupload({disabled:false});
	} else {
	    $msg = dotclear.msg.enhanced_uploader_activate;
	}

	$('<div><a href="#">' + $msg + '</a></div>').click( function() {
	    if ($container.hasClass('enhanced_uploader')) {
		$msg = dotclear.msg.enhanced_uploader_activate;
		$('#upfile').attr('multiple', false);

		// when a user has clicked enhanced_uploader, and has added files
		// We must remove files in table
		$('.table-files tr', '#fileupload').remove();
		$('#fileupload').fileupload({disabled:true});
	    } else {
		$msg = dotclear.msg.enhanced_uploader_disable;
		$('#upfile').attr('multiple', true);
		$('#fileupload').fileupload({disabled:false});
	    }
	    $(this).find('a').text($msg);

	    $container.toggleClass('enhanced_uploader');
	}).appendTo($('#fileupload'));
    }

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
