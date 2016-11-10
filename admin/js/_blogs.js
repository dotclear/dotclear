$(function() {
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this,undefined,'#form-blogs td input[type=checkbox]','#form-blogs #do-action');
	});
	$('#form-blogs td input[type=checkbox]').enableShiftClick();
	dotclear.condSubmit('#form-blogs td input[type=checkbox]','#form-blogs #do-action');
});
