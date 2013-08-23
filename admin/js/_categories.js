$(function() {
	if ($.fn['nestedSortable']!==undefined) {
		$('#categories ul li').css('cursor','move');
		$('#save-set-order').prop('disabled',true).addClass('disabled');
		$('#categories ul').nestedSortable({
			listType: 'ul',
			items: 'li',
			placeholder: 'placeholder',
			update: function() {
				$('#categories_order').attr('value',JSON.stringify($('#categories ul').nestedSortable('toArray')));
				$('#save-set-order').prop('disabled',false).removeClass('disabled');
			}
		});
	}

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
