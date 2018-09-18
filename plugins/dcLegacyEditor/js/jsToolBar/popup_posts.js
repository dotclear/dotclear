/*global $ */
'use strict';

$(function() {
	$('#link-insert-cancel').click(function() {
		window.close();
	});

	$('#form-entries tr>td.maximal>a').click(function() {
		// Get post_id
		const tb = window.opener.the_toolbar;
		const data = tb.elements.link.data;

		data.href = tb.stripBaseURL($(this).attr('title'));

		tb.elements.link.fncall[tb.mode].call(tb);
		window.close();
	});
});
