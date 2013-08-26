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

	$('input[name="delete"]').click(function() {
		var nb_ckecked = $('input[name="categories[]"]:checked').length;
		if (nb_ckecked==0) {
			return false;
		}

		return window.confirm(dotclear.msg.confirm_delete_categories.replace('%s',nb_ckecked));
	});

	$('input[name="reset"]').click(function() {
		return window.confirm(dotclear.msg.confirm_reorder_categories);
	});
});
