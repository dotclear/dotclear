dotclear.postExpander = function(line) {
	var title = $(line).find('.widget-name');
	
	var img = document.createElement('img');
	img.src = dotclear.img_plus_src;
	img.alt = dotclear.img_plus_alt;
	img.className = 'expand';
	$(img).css('cursor','pointer');
	img.line = line;
	img.onclick = function() { dotclear.viewPostContent(this.line); };
	
	title.prepend(img);
};

dotclear.viewPostContent = function(line,action) {
	var action = action || 'toogle';
	var img = $(line).find('.expand');
	var isopen = img.attr('alt') == dotclear.img_plus_alt;
	
	if( action == 'close' || ( action == 'toogle' && !isopen ) ) {
		$(line).find('.widgetSettings').hide();
		img.attr('src', dotclear.img_plus_src);
		img.attr('alt', dotclear.img_plus_alt);
	} else if ( action == 'open' || ( action == 'toogle' && isopen ) ) {
		$(line).find('.widgetSettings').show();
		img.attr('src', dotclear.img_minus_src);
		img.attr('alt', dotclear.img_minus_alt);
	}
	
};

$(function() {	
	$('input[name="wreset"]').click(function() {
		return window.confirm(dotclear.msg.confirm_widgets_reset);
	});
	
	$('#dndnav > li, #dndextra > li, #dndcustom > li').each(function() {
		dotclear.postExpander(this);
		dotclear.viewPostContent(this, 'close');
	});
});
	
	/*var widgets = document.getElementById('widgets');
	var w_nav = document.getElementById('dndnav');
	var w_ext = document.getElementById('dndextra');
	var w_custom = document.getElementById('dndcustom');
	
	$('#dndnav').addClass('hideControls');
	$('#dndextra').addClass('hideControls');
	$('#dndcustom').addClass('hideControls');
	
	removeElements(document.getElementById('listWidgets'),'input');
	removeElements(widgets,'p');
	removeElements(w_nav,'p');
	removeElements(w_ext,'p');
	removeElements(w_custom,'p');
	hideElements(w_nav,'input');
	hideElements(w_ext,'input');
	hideElements(w_custom,'input');
	
	configControls(w_nav);
	configControls(w_ext);
	configControls(w_custom);
	
	// Helper to remove some elements
	function removeElements(p,name) {
		name = name || 'div';
		$(p).find(name+'.js-remove').each(function() {
			this.parentNode.removeChild(this);
		});
	}
	
	// Helper to hide elements (but keep them)
	function hideElements(p,name) {
		name = name || 'div';
		$(p).find(name+'.js-hide').each(function() {
			$(this).toggle();
		});
	}	
	
	function removeEmptyMsg(p) {
		$(p).find('p.empty-widgets').each(function() {
			this.parentNode.removeChild(this);
		});
	}
	
	// Events on dragEnd
	function navDragEnd() {
		formControls(this.parentNode,'nav');
		configControls(this.parentNode);
		removeEmptyMsg(this.parentNode);
	}
	function extraDragEnd() {
		formControls(this.parentNode,'extra');
		configControls(this.parentNode);
		removeEmptyMsg(this.parentNode);
	}
	function customDragEnd() {
		formControls(this.parentNode,'custom');
		configControls(this.parentNode);
		removeEmptyMsg(this.parentNode);
	}
	
	// dragEnd helper
	function formControls(e,pr) {
		var items = new Array();
		for (var i=0; i<e.childNodes.length; i++) {
			if (e.childNodes[i].nodeType == 1 && e.childNodes[i].nodeName.toLowerCase() == 'div') {
				items.push(e.childNodes[i]);
			}
		}
		
		var fields, itype;
		var r = new RegExp('^w\[[a-z]+]\[[0-9]+][[](.+?)]$','');
		for (i=0; i<items.length; i++) {
			// Change field names
			fields = getFormControls(items[i]);
			var j;
			var f;
			for (j=0; j<fields.length; j++) {
				if (r.test(fields[j].name)) {
					itype = fields[j].name.replace(r,'$1');
					
					$(fields[j]).attr('name','w['+pr+']['+i+']['+itype+']');
					
					if (itype == 'order') {
						fields[j].value = i;
					}
				}
			}
		}
	}
	
	function getFormControls(e) {
		var input = e.getElementsByTagName('input');
		var textarea = e.getElementsByTagName('textarea');
		var select = e.getElementsByTagName('select');
		var items = new Array();
		var i;
		for (i=0; i<input.length; i++) { items.push(input[i]); }
		for (i=0; i<select.length; i++) { items.push(select[i]); }
		for (i=0; i<textarea.length; i++) { items.push(textarea[i]); }
		
		return items;
	}
	
	function configControls(e) {
		var items = new Array();
		for (var i=0; i<e.childNodes.length; i++) {
			if (e.childNodes[i].nodeType == 1 && e.childNodes[i].nodeName.toLowerCase() == 'div') {
				items.push(e.childNodes[i]);
			}
		}
		
		var title, img_ctrl, img, space;
		for (i in items) {
			// Append config control
			title = $('p.widget-name',items[i]).get(0);
			img_ctrl = title.firstChild;
			
			// There already an image
			if (img_ctrl.nodeName.toLowerCase() == 'img') {
				continue;
			}
			
			// Nothing to configure
			if (title.nextSibling.childNodes.length == 0) {
				continue;
			}
			
			img = document.createElement('img');
			img.src = dotclear.img_plus_src;
			img.alt = dotclear.img_plus_alt;
			img.control = title.nextSibling;
			img.onclick = function() { widgetConfig.call(this); };
			space = document.createTextNode(' ');
			title.insertBefore(img,img_ctrl);
			title.insertBefore(space,img_ctrl);
		}
	}
	
	function widgetConfig() {
		if (this.control.style.display == 'block') {
			this.control.style.display = 'none';
			this.src = dotclear.img_plus_src;
			this.alt = dotclear.img_plus_alt;
		} else {
			this.control.style.display = 'block';
			this.src = dotclear.img_minus_src;
			this.alt = dotclear.img_minus_alt;
		}
	}
});

//
// For Safari, we need to make a hard copy of the form and submit copy.
// Yeah, a simple clone of the form is not enough
// I hate this browser!
//
if (document.childNodes && !document.all && !navigator.taintEnabled)
{
	$(function() {
		$('#sidebarsWidgets').submit(function() {
			// Create a new form and copy each element inside
			var f = document.createElement('form');
			f.action = this.action;
			f.method = this.method;
			$(f).hide();
			var fset = document.createElement('fieldset');
			f.appendChild(fset);
			
			document.body.appendChild(f);
			
			$(this).find('input').each(function() {
				var i = document.createElement('input');
				i.type = this.type;
				i.name = this.name;
				i.value = this.value;
				if (this.type == 'checkbox') {
					i.checked = this.checked;
				}
				fset.appendChild(i);
			});
			
			$(this).find('textarea').each(function() {
				var i = document.createElement('textarea');
				i.name = this.name;
				i.value = this.value;
				fset.appendChild(i);
			});
			
			$(this).find('select').each(function() {
				var i = document.createElement('input');
				i.name = this.name;
				i.value = this.value;
				fset.appendChild(i);
			});
			
			$(fset).append('<input type="hidden" name="wup" value="1"/>');
			f.submit();
			
			return false;
		});
	});
}*/