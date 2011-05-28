ToolMan._coordinatesFactory.inside = function(item,container) {
	var iTL = this.topLeftOffset(item);
	var iBR = this.bottomRightOffset(item);
	var cTL = this.topLeftOffset(container);
	var cBR = this.bottomRightOffset(container);
	
	return (iTL.x >= cTL.x && iTL.x <= cBR.x && iTL.y >= cTL.y && iTL.y <= cBR.y)
	|| (iBR.x >= cTL.x && iBR.x <= cBR.x && iBR.y >= cTL.y && iBR.y <= cBR.y);
}

ToolMan._dragdropFactory = {
	firstContainer: null,
	lastContainer: null,
	
	makeDragable: function(item) {
		var group = ToolMan.drag().createSimpleGroup(item);
		group.register('dragstart',this._onDragStart);
		group.register('dragmove', this._onDragMove);
		group.register('dragend', this._onDragEnd);
		
		item.isOutside = false;
		item.started = false;
		
		return group;
	},
	
	makeListContainer: function(list,name) {
		// each container becomes a linked list node
		if (this.firstContainer == null) {
			this.firstContainer = this.lastContainer = list;
			list.previousContainer = null;
			list.nextContainer = null;
		} else {
			list.previousContainer = this.lastContainer;
			list.nextContainer = null;
			this.lastContainer.nextContainer = list;
			this.lastContainer = list;
		}
		
		var helpers = ToolMan.helpers();
		var coordinates = ToolMan.coordinates();
		
		//var children = list.childNodes;
		var items = new Array();
		for (var i=0; i<list.childNodes.length; i++) {
			if (list.childNodes[i].nodeType == 1 &&
			list.childNodes[i].nodeName.toLowerCase() == name.toLowerCase()) {
				items.push(list.childNodes[i]);
			}
		}
		
		// these functions are called when an item is draged over
		// a container or out of a container bounds.  onDragOut
		// is also called when the drag ends with an item having
		// been added to the container
		list.onDragOver = new Function();
		list.onDragOut = new Function();
		list.onDragEnd = new Function();
		
		if (list.factory == undefined) {
			list.factory = false;
		}
		
		var This = this;
		helpers.map(items, function(item) {
			var dragGroup = This.makeDragable(item);
			dragGroup.setThreshold(4);
		});
		for (var i = 2, n = arguments.length; i < n; i++) {
			helpers.map(items, arguments[i]);
		}
	},
	
	_onDragStart: function(dragEvent) {
		var container = ToolMan._dragdropFactory.firstContainer;
		var item = dragEvent.group.element;
		var coordinates = ToolMan.coordinates();
		
		// Factory is a sort of assembly pieces, we want to keep them
		if (item.parentNode.factory) {
			var origin = item.cloneNode(true);
			item.parentNode.insertBefore(origin,item.nextSibling);
			ToolMan._dragdropFactory.makeDragable(origin);
		}
		
		while (container != null) {
			container.topLeft = coordinates.topLeftOffset(container);
			container.bottomRight = coordinates.bottomRightOffset(container);
			container = container.nextContainer;
		}
		
		// item starts out over current parent
		item.started = true;
		item.parentNode.onDragOver();
	},
	
	_onDragMove: function(dragEvent) {
		var helpers = ToolMan.helpers();
		var coordinates = ToolMan.coordinates();
		
		var item = dragEvent.group.element;
		var xmouse = dragEvent.transformedMouseOffset;
		var moveTo = null;
		
		// Check if we are nowhere
		if (item.isOutside) {
			var container = ToolMan._dragdropFactory.firstContainer;
			while (container != null) {
				if (coordinates.inside(item,container) && !container.factory) {
					container.onDragOver();
					item.isOutside = false;
					
					// since isOutside was true, the current parent is a
					// temporary clone of some previous container node and
					// it needs to be removed from the document
					var tempParent = item.parentNode;
					tempParent.removeChild(item);
					container.appendChild(item);
					//tempParent.parentNode.removeChild(tempParent);
					
					break;
				}
				container = container.nextContainer;
			}
			
			if (this.isOutside) {
				return;
			}
		}
		// Outside the parent node
		else if (!coordinates.inside(item,item.parentNode)) {
			item.parentNode.onDragOut();
			item.isOutside = true;
			var container = ToolMan._dragdropFactory.firstContainer;
			while (container != null) {
				if (coordinates.inside(item,container) && !container.factory) {
					container.onDragOver();
					item.isOutside = false;
					container.appendChild(item);
					break;
				}
				container = container.nextContainer;
			}
			// if we're not in any container now, make a temporary clone of
			// the previous container node and add it to the document
			if (this.isOutside) {
				var tempParent = item.parentNode.cloneNode(false);
				item.parentNode.removeChild(item);
				tempParent.appendChild(item);
				document.body.appendChild(tempParent);
				return;
			}
		}
		
		// if we get here, we're inside some container bounds, so we do
		// everything the original dragsort script did to swap us into the
		// correct position
		
		if (item.parentNode.factory) {
			return;
		}
		
		var moveTo = null
		
		var previous = helpers.previousItem(item, item.nodeName)
		while (previous != null) {
			var bottomRight = coordinates.bottomRightOffset(previous)
			if (xmouse.y <= bottomRight.y && xmouse.x <= bottomRight.x) {
				moveTo = previous
			}
			previous = helpers.previousItem(previous, item.nodeName)
		}
		if (moveTo != null) {
			helpers.moveBefore(item, moveTo)
			return
		}
		
		var next = helpers.nextItem(item, item.nodeName)
		while (next != null) {
			var topLeft = coordinates.topLeftOffset(next)
			if (topLeft.y <= xmouse.y && topLeft.x <= xmouse.x) {
				moveTo = next
			}
			next = helpers.nextItem(next, item.nodeName)
		}
		if (moveTo != null) {
			helpers.moveBefore(item, helpers.nextItem(moveTo, item.nodeName))
			return
		}
	},
	
	_onDragEnd: function(dragEvent) {
		var item = dragEvent.group.element;
		
		if (!item.started) {
			return;
		}
		
		if (item.isOutside || item.parentNode.factory) {
			item.parentNode.removeChild(item);
			return;
		}
		
		item.parentNode.onDragEnd.call(item);
		ToolMan.coordinates().create(0, 0).reposition(dragEvent.group.element);
	}
};

ToolMan.dragdrop = function() {
	return ToolMan._dragdropFactory;
};