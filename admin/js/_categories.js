/* TODO: Some nice drag and drop on categories */
$(function() {
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});

	$('#mov_cat').parent().hide();
	$('input[name="categories[]"]').click(function() {
		$('#mov_cat').parent().parent().removeClass('two-cols').addClass('three-cols');
		$('#mov_cat').parent().show();
	});

	dotclear.categoriesActionsHelper();


	$('form#reset-order').submit(function() {
		return window.confirm(dotclear.msg.confirm_reorder_categories);
	});
});
