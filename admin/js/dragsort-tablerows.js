ToolMan._dragsortFactory.makeTableSortable = function(table) {
	if (table == null) return;
	
	var helpers = ToolMan.helpers();
	var coordinates = ToolMan.coordinates();
	var items = table.getElementsByTagName("tr");
	
	helpers.map(items, function(item) {
		var dragGroup = dragsort.makeSortable(item);
		dragGroup.setThreshold(4);
		var min, max;
		dragGroup.addTransform(function(coordinate, dragEvent) {
			return coordinate.constrainTo(min, max);
		});
		dragGroup.register('dragstart', function() {
			var items = table.getElementsByTagName("tr");
			min = max = coordinates.topLeftOffset(items[0]);
			for (var i = 1, n = items.length; i < n; i++) {
				var offset = coordinates.topLeftOffset(items[i]);
				min = min.min(offset);
				max = max.max(offset);
			}
		});
	});
	for (var i = 1, n = arguments.length; i < n; i++) {
		helpers.map(items, arguments[i]);
	}
};

ToolMan._dragsortFactory.registerOrder = function(dest) { };