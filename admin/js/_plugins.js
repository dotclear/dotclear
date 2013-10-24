$(function() {
	// expand a module line
	$('table.modules.expandable tr.line').each(function(){
		$('td.module-name',this).toggleWithLegend($(this).next('.module-more'),{
			img_on_src: dotclear.img_plus_src,
			img_on_alt: dotclear.img_plus_alt,
			img_off_src: dotclear.img_minus_src,
			img_off_alt: dotclear.img_minus_alt,
			legend_click: true
		});
	});

	// checkboxes selection
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});

	// actions tests
	$('.modules-form-actions').each(function(){
		var rxActionType = /^[^\[]+/;
		var rxActionValue = /([^\[]+)\]$/;
		var checkboxes = $(this).find('input[type=checkbox]');

		// check if submit is a global action or one line action
		$("input[type=submit]",this).click(function() {
			var keyword = $(this).attr('name');
			var maction = keyword.match(rxActionType);
			var action = maction[0];
			var mvalues = keyword.match(rxActionValue);

			// action on multiple modules
			if (!mvalues) {
				var checked = false;

				// check if there is checkboxes in form
				if(checkboxes.length > 0) {
					// check if there is at least one checkbox checked
					$(checkboxes).each(function() {
						if (this.checked) {
							checked = true;
						}
					});
					if (!checked) {
						//alert(dotclear.msg.no_selection);
						return false;
					}
				}

				// confirm delete
				if (action == 'delete') {
					return window.confirm(dotclear.msg.confirm_delete_plugins);
				}

			// action on one module
			}else {
				var module = mvalues[1];

				// confirm delete
				if (action == 'delete') {
					return window.confirm(dotclear.msg.confirm_delete_plugin.replace('%s',module));
				}
			}

			return true;
		});
	});
});