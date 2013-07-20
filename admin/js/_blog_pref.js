function checkQueryString() {
	var blogUrl = $('#blog_url')[0].value;
	var urlScan = $('#url_scan')[0].value;
	errorMsg = '';
	if (/.*[^\/]$/.exec(blogUrl) && urlScan=='path_info') {
		errorMsg = dotclear.msg.warning_path_info;
	} else if (/.*[^\?]$/.exec(blogUrl) && urlScan=='query_string') {
		errorMsg = dotclear.msg.warning_query_string;
	}
	$("p#urlwarning").remove();
	if (errorMsg != '') {
		$("#blog_url").parents('p').after('<p id="urlwarning" class="warning">'+errorMsg+'</p>');
	}
}


$(function() {
	checkQueryString();
	$('#blog_url').focusout(checkQueryString);
	$('#url_scan').live("change",checkQueryString);
});