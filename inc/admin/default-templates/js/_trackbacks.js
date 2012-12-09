$(function() {
	$('#tb_excerpt').keypress(function() {
		if (this.value.length > 255) {
			this.value = this.value.substring(0,255);
		}
	});
});