$(function() {
	$('h5.s-attachments').toggleWithLegend($('.s-attachments').not('h5'),{
		cookie: 'dcx_attachments'
	});
});