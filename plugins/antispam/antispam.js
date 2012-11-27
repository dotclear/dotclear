var dragsort = ToolMan.dragsort();
$(function() {
	$("#filters-list").each(function() {
		dragsort.makeTableSortable(this,dotclear.sortable.setHandle, dotclear.sortable.saveOrder);
	});
	$('form input[type=submit][name=delete_all]').click(function(){
		return window.confirm(dotclear.msg.confirm_spam_delete);
	});
});

dotclear.sortable = {
	setHandle: function(item) {
		var handle = $(item).find('td.handle').get(0);
		while (handle.firstChild) {
			handle.removeChild(handle.firstChild);
		}
		
		item.toolManDragGroup.setHandle(handle);
		$(handle).addClass('handler');
	},
	
	saveOrder: function(item) {
		var group = item.toolManDragGroup;
		var order = $('#filters_order').get(0);
		group.register('dragend', function() {
			order.value = '';
			items = item.parentNode.getElementsByTagName('tr');
			
			for (var i=0; i<items.length; i++) {
				order.value += items[i].id.substr(2)+',';
			}
		});
	}
};