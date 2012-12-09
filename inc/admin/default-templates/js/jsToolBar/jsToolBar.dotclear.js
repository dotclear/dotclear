/* Change link button actions
-------------------------------------------------------- */
jsToolBar.prototype.elements.link.data = {};
jsToolBar.prototype.elements.link.fncall = {};
jsToolBar.prototype.elements.link.open_url = 'popup_link.php';

jsToolBar.prototype.elements.link.popup = function (args) {
	window.the_toolbar = this;
	args = args || '';
	
	this.elements.link.data = {};
	var url = this.elements.link.open_url+args;
	
	var p_win = window.open(url,'dc_popup',
	'alwaysRaised=yes,dependent=yes,toolbar=yes,height=420,width=520,'+
	'menubar=no,resizable=yes,scrollbars=yes,status=no');
};

jsToolBar.prototype.elements.link.fn.wiki = function() {
	this.elements.link.popup.call(this,'?hreflang='+this.elements.link.default_hreflang);
};
jsToolBar.prototype.elements.link.fncall.wiki = function() {
	var data = this.elements.link.data;
	
	if (data.href == '') { return; }
	
	var etag = '|'+data.href;
	if (data.hreflang) { etag += '|'+data.hreflang; }
	
	if (data.title) {
		if (!data.hreflang) { etag += '|'; }
		etag += '|'+data.title;
	}
	
	if (data.content) {
		this.encloseSelection('['+data.content,etag+']');
	} else {
		this.encloseSelection('[',etag+']');
	}
};

jsToolBar.prototype.elements.link.fn.xhtml = function() {
	this.elements.link.popup.call(this,'?hreflang='+this.elements.link.default_hreflang);
};
jsToolBar.prototype.elements.link.fncall.xhtml = function() {
	var data = this.elements.link.data;
	
	if (data.href == '') { return; }
	
	var stag = '<a href="'+data.href+'"';
	
	if (data.hreflang) { stag += ' hreflang="'+data.hreflang+'"'; }
	if (data.title) { stag += ' title="'+data.title+'"'; }
	stag += '>';
	var etag = '</a>';
	
	if (data.content) {
		this.encloseSelection('','',function() {
			return stag + data.content + etag;
		});
	} else {
		this.encloseSelection(stag,etag);
	}
};

jsToolBar.prototype.elements.link.fn.wysiwyg = function() {
	var href, title, hreflang;
	href = title = hreflang = '';
	hreflang = this.elements.link.default_hreflang;
	
	var a = this.getAncestor();
	
	if (a.tagName == 'a') {
		href= a.tag.href || '';
		title = a.tag.title || '';
		hreflang = a.tag.hreflang || '';
	}
	
	this.elements.link.popup.call(this,'?href='+href+'&hreflang='+hreflang+'&title='+title);
};
jsToolBar.prototype.elements.link.fncall.wysiwyg = function() {
	var data = this.elements.link.data;
	
	var a = this.getAncestor();
	
	if (a.tagName == 'a') {
		if (data.href == '') {
			// Remove link
			this.replaceNodeByContent(a.tag);
			this.iwin.focus();
			return;
		} else {
			// Update link
			a.tag.href = data.href;
			if (data.hreflang) {
				a.tag.setAttribute('hreflang',data.hreflang);
			} else {
				a.tag.removeAttribute('hreflang');
			}
			if (data.title) {
				a.tag.setAttribute('title',data.title);
			} else {
				a.tag.removeAttribute('title');
			}
			return;
		}
	}
	
	// Create link
	if (data.content) {
		var n = document.createTextNode(data.content);
	} else {
		var n = this.getSelectedNode();
	}
	var a = this.iwin.document.createElement('a');
	a.href = data.href;
	if (data.hreflang) a.setAttribute('hreflang',data.hreflang);
	if (data.title) a.setAttribute('title',data.title);
	a.appendChild(n);
	this.insertNode(a);
};
jsToolBar.prototype.getAncestor = function() {
	var res = {};
	var range, commonAncestorContainer;
	
	if (this.iwin.getSelection) { //gecko
		var selection = this.iwin.getSelection();
		range = selection.getRangeAt(0);
		commonAncestorContainer = range.commonAncestorContainer;
		while (commonAncestorContainer.nodeType != 1) {
			commonAncestorContainer = commonAncestorContainer.parentNode;
		}
	} else { //ie
		range = this.iwin.document.selection.createRange();
		commonAncestorContainer = range.parentElement();
	}
	
	var ancestorTagName = commonAncestorContainer.tagName.toLowerCase();
	while (ancestorTagName!='a' && ancestorTagName!='body') {
		commonAncestorContainer = commonAncestorContainer.parentNode;
		ancestorTagName = commonAncestorContainer.tagName.toLowerCase();
	}
	
	res.tag = commonAncestorContainer;
	res.tagName = ancestorTagName;
	
	return res;
};

/* Image selector
-------------------------------------------------------- */
jsToolBar.prototype.elements.img_select = {
	type: 'button',
	title: 'Image chooser',
	fn: {},
	fncall: {},
	open_url: 'media.php?popup=1',
	data: {},
	popup: function() {
		window.the_toolbar = this;
		this.elements.img_select.data = {};
		
		var p_win = window.open(this.elements.img_select.open_url,'dc_popup',
		'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,'+
		'menubar=no,resizable=yes,scrollbars=yes,status=no');
	}
};
jsToolBar.prototype.elements.img_select.fn.wiki = function() {
	this.elements.img_select.popup.call(this);
};
jsToolBar.prototype.elements.img_select.fncall.wiki = function() {
	var d = this.elements.img_select.data;
	if (d.src == undefined) { return; }
	
	this.encloseSelection('','',function(str) {
		var alt = (str) ? str : d.title;
		var res = '(('+d.src+'|'+alt;
		
		if (d.alignment == 'left') {
			res += '|L';
		} else if (d.alignment == 'right') {
			res += '|R';
		} else if (d.alignment == 'center') {
			res += '|C';
		} else if (d.description) {
			res += '|';
		}
		
		if (d.description) {
			res += '|'+d.description;
		}
		
		res += '))';
		
		if (d.link) {
			var ltitle = (alt) ? '||'+alt : '';
			res = '['+res+'|'+d.url+ltitle+']';
		}
		
		return res;
	});
};
jsToolBar.prototype.elements.img_select.fn.xhtml = function() {
	this.elements.img_select.popup.call(this);
};
jsToolBar.prototype.elements.img_select.fncall.xhtml = function() {
	var d = this.elements.img_select.data;
	if (d.src == undefined) { return; }
	
	this.encloseSelection('','',function(str) {
		var alt = (str) ? str : d.title;
		var res = '<img src="'+d.src+'" alt="'+alt.replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;')+'"';
		
		if (d.alignment == 'left') {
			res += ' style="float: left; margin: 0 1em 1em 0;"';
		} else if (d.alignment == 'right') {
			res += ' style="float: right; margin: 0 0 1em 1em;"';
		} else if (d.alignment == 'center') {
			res += ' style="margin: 0 auto; display: block;"';
		}
		
		if (d.description) {
			res += ' title="'+d.description.replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;')+'"';
		}
		
		res += ' />';
		
		if (d.link) {
			var ltitle = (alt) ? ' title="'+alt.replace('&','&amp;').replace('>','&gt;').replace('<','&lt;').replace('"','&quot;')+'"' : '';
			res = '<a href="'+d.url+'"'+ltitle+'>'+res+'</a>';
		}
		
		return res;
	});
};

jsToolBar.prototype.elements.img.fn.wysiwyg = function() {
	var src = this.elements.img.prompt.call(this);
	if (!src) { return; }
	
	var img = this.iwin.document.createElement('img');
	img.src = src;
	img.setAttribute('alt',this.getSelectedText());
	
	this.insertNode(img);
};

jsToolBar.prototype.elements.img_select.fn.wysiwyg = function() {
	this.elements.img_select.popup.call(this);
};
jsToolBar.prototype.elements.img_select.fncall.wysiwyg = function() {
	var d = this.elements.img_select.data;
	var alt = (this.getSelectedText()) ? this.getSelectedText() : d.title;
	if (d.src == undefined) { return; }
	
	var img = this.iwin.document.createElement('img');
	img.src = d.src;
	img.setAttribute('alt',alt);
	
	
	if (d.alignment == 'left') {
		if (img.style.styleFloat != undefined) {
			img.style.styleFloat = 'left';
		} else {
			img.style.cssFloat = 'left';
		}
		img.style.marginTop = 0;
		img.style.marginRight = '1em';
		img.style.marginBottom = '1em';
		img.style.marginLeft = 0;
	} else if (d.alignment == 'right') {
		if (img.style.styleFloat != undefined) {
			img.style.styleFloat = 'right';
		} else {
			img.style.cssFloat = 'right';
		}
		img.style.marginTop = 0;
		img.style.marginRight = 0;
		img.style.marginBottom = '1em';
		img.style.marginLeft = '1em';
	} else if (d.alignment == 'center') {
		img.style.marginTop = 0;
		img.style.marginRight = 'auto';
		img.style.marginBottom = 0;
		img.style.marginLeft = 'auto';
		img.style.display = 'block';
	}
	
	if (d.description) {
		img.setAttribute('title',d.description);
	}
	
	if (d.link) {
		var a = this.iwin.document.createElement('a');
		a.href = d.url;
		if (alt) {
			a.setAttribute('title',alt);
		}
		a.appendChild(img);
		this.insertNode(a);
	} else {
		this.insertNode(img);
	}
};

// MP3 helpers
jsToolBar.prototype.elements.mp3_insert = { fncall: {}, data: {} };
jsToolBar.prototype.elements.mp3_insert.fncall.wiki = function() {
	var d = this.elements.mp3_insert.data;
	if (d.player == undefined) { return; }
	
	this.encloseSelection('','',function(str) {
		return '\n///html\n' + d.player + '///\n';
	});
};
jsToolBar.prototype.elements.mp3_insert.fncall.xhtml = function() {
	var d = this.elements.mp3_insert.data;
	if (d.player == undefined) { return; }
	
	this.encloseSelection('','',function(str) {
		return '\n' + d.player + '\n';
	});
};
jsToolBar.prototype.elements.mp3_insert.fncall.wysiwyg = function() {
	return;
};

// FLV helpers
jsToolBar.prototype.elements.flv_insert = { fncall: {}, data: {} };
jsToolBar.prototype.elements.flv_insert.fncall.wiki = function() {
	var d = this.elements.flv_insert.data;
	if (d.player == undefined) { return; }
	
	this.encloseSelection('','',function(str) {
		return '\n///html\n' + d.player + '///\n';
	});
};
jsToolBar.prototype.elements.flv_insert.fncall.xhtml = function() {
	var d = this.elements.flv_insert.data;
	if (d.player == undefined) { return; }
	
	this.encloseSelection('','',function(str) {
		return '\n' + d.player + '\n';
	});
};
jsToolBar.prototype.elements.flv_insert.fncall.wysiwyg = function() {
	return;
};


/* Posts selector
-------------------------------------------------------- */
jsToolBar.prototype.elements.post_link = {
	type: 'button',
	title: 'Link to an entry',
	fn: {},
	open_url: 'popup_posts.php',
	data: {},
	popup: function() {
		window.the_toolbar = this;
		this.elements.img_select.data = {};
		
		var p_win = window.open(this.elements.post_link.open_url,'dc_popup',
		'alwaysRaised=yes,dependent=yes,toolbar=yes,height=500,width=760,'+
		'menubar=no,resizable=yes,scrollbars=yes,status=no');
	}
};
jsToolBar.prototype.elements.post_link.fn.wiki = function() {
	this.elements.post_link.popup.call(this);
};
jsToolBar.prototype.elements.post_link.fn.xhtml = function() {
	this.elements.post_link.popup.call(this);
};
jsToolBar.prototype.elements.post_link.fn.wysiwyg = function() {
	this.elements.post_link.popup.call(this);
};

// Last space element
jsToolBar.prototype.elements.space3 = {type: 'space'};