$(function() {
	$('#link-insert-cancel').click(function() {
		window.close();
	});
	
	$('#link-insert-ok').click(function() {
		sendClose();
		window.close();
	});
	
	function sendClose() {
		var insert_form = $('#link-insert-form').get(0);
		if (insert_form == undefined) { return; }
		
		var tb = window.opener.the_toolbar;
		var data = tb.elements.link.data;
		
		data.href = tb.stripBaseURL(insert_form.elements.href.value);
		data.title = insert_form.elements.title.value;
		data.hreflang = insert_form.elements.hreflang.value;
		tb.elements.link.fncall[tb.mode].call(tb);
	};
});