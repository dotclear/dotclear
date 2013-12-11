$(function() {
	dotclear.hideLockable();

	if ($.isFunction('jsToolBar')) {
		var tbCategory = new jsToolBar(document.getElementById('cat_desc'));
		tbCategory.draw('xhtml');
	}
});
