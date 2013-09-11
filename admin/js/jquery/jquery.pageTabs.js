jQuery.pageTabs = function(start_tab,settings) {
	return new jQuery._pageTabs(start_tab,settings);
};

jQuery._pageTabs = function(start_tab,settings) {
	var defaults = {
		className: 'multi-part',
		listClassName: 'part-tabs',
		breakerClassName: 'clear'
	};

	var index = start_tab ? start_tab : 0;
	var hash = document.location.hash.split('#').join('');
	if( hash != '' ) {
		var index = hash;
	}
	
	this.params = jQuery.extend(defaults,settings);
	this.divs = jQuery('div.'+this.params.className);
	this.createList();
	this.showDiv(index);
	var pageTabs = this;
	
	window.onhashchange = function (event) {
		pageTabs.showDiv(document.location.hash.split('#').join(''));
    }
};

jQuery._pageTabs.prototype = {
	items: new Array(),

	createList: function() {
		if (this.divs.length <= 0) {
			return;
		}

		this.block = document.createElement('div');
		this.block.className = this.params.listClassName;
		this.list = document.createElement('ul');
		//this.list.className = this.params.listClassName;
		this.block.appendChild(this.list);
		var li, a;

		var This = this;
		var i=0;
		jQuery('.'+this.params.className).each(function() {
			if (this.tagName == "DIV") {
				li = document.createElement('li');
				a = document.createElement('a');
				$(a).html(this.title);
				this.title = '';
				a.fn = This.showDiv;
				a.index = this.id || i;
				a.href = '#'+a.index;
				li.id = "part-tabs-"+a.index;
				a.obj = This;
				li.appendChild(a);
				This.list.appendChild(li);
				This.items[i] = li;
				i++;
			} else {
				li = document.createElement('li');
				li.className = This.params.listClassName+'-link';
				li.appendChild(this);
				This.list.appendChild(li);
			}
		});

		this.breaker = document.createElement('br');
		this.breaker.className = this.params.breakerClassName;

		jQuery(this.divs.get(0)).before(this.block);
		jQuery(this.block).after(this.breaker);
	},

	showDiv: function(index) {
		var This = this;
		var i = 0;
		var to_trigger = null;
		var exists = false;

		this.divs.each(function() {
			if ((this.id != '' && this.id == index) || i == index) {
				exists = true;
			}
			i++;
		});
		
		i = 0;
		
		if( exists ) {
			this.divs.each(function() {
				if ((this.id != '' && this.id == index) || i == index) {
					jQuery(this).show(0);
					This.items[i].className = This.params.listClassName+'-active';
					to_trigger = i;
				} else {
					jQuery(this).hide(0);
					This.items[i].className = '';
				}
	
				i++;
			});
		}

		if (to_trigger != null) {
			jQuery(this.divs[to_trigger]).onetabload();
			jQuery(this.divs[to_trigger]).tabload();
		}
	}
};

jQuery.fn.tabload = function(f) {
	this.each(function() {
		if (f) {
			chainHandler(this,'tabload',f)
		} else {
			var h = this.tabload;
			if (h) { h.apply(this); }
		}
	});
	return this;
};
jQuery.fn.onetabload = function(f) {
	this.each(function() {
		if (f) {
			chainHandler(this,'onetabload',f);
		} else {
			var h = this.onetabload;
			if (h != null) {
				h.apply(this);
				this.onetabload = null;
			}
		}
	});
	return this;
};
