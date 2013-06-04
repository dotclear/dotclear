$(function() {
    $('#fileupload')
	.fileupload({
	    url: $('#fileupload').attr('action'),
	    autoUpload: false
	});

    // Load existing files:
    $('#fileupload').addClass('fileupload-processing');
    $.ajax({
        url: $('#fileupload').fileupload('option', 'url'),
        dataType: 'json',
        context: $('#fileupload')[0]
    }).always(function (result) {
        $(this).removeClass('fileupload-processing');
    }).done(function (result) {
        $(this).fileupload('option', 'done')
            .call(this, null, {result: result});
    });

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
});
