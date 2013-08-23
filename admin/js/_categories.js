/* TODO: Some nice drag and drop on categories */
$(function() {
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});

	dotclear.categoriesActionsHelper();


	$('form#reset-order').submit(function() {
		return window.confirm(dotclear.msg.confirm_reorder_categories);
	});
});
