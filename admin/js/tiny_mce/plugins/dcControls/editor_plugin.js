(function() {
	tinymce.create('tinymce.plugins.dcControls',{
		// Init.
		init: function(ed, url) {
			var t = this;
			var popup_link_url = 'popup_link.php';
			var popup_media_url = 'media.php?popup=1';
			var popup_web_media_url = 'popup_web_media.php';
			
			t.editor = ed;
			
			// Register commands
			ed.addCommand('dcCode', function() {
				ed.formatter.toggle('inlinecode');
			});
			ed.addCommand('dcQuote', function() {
				ed.formatter.toggle('quote');
			});
			ed.addCommand('dcLink', function() {
				var se = ed.selection;
				
				// No selection and not in link
				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'A'))
					return;
				
				var url = popup_link_url;
				var node = se.getNode();
				
				if (node.nodeName == 'A') {
					var href= node.href || '';
					var title = node.title || '';
					var hreflang = node.hreflang || '';
					url += '?href='+href+'&hreflang='+hreflang+'&title='+title;
				}
				ed.windowManager.open({
					file: url,
					width: 760,
					height: 500,
					inline: 1,
					popup_css : false,
					dc_popup: '',
					alwaysRaised: 'yes',
					dependent: 'yes',
					toolbar: 'yes',
					menubar: 'no',
					resizable: 'yes',
					scrollbars: 'yes',
					status: 'no'
				}, {
					plugin_url : url
				});
			});
			ed.addCommand('dcMedia', function() {
				ed.windowManager.open({
					file: popup_media_url,
					width: 760,
					height: 500,
					inline: 1,
					popup_css : false,
					dc_popup: '',
					alwaysRaised: 'yes',
					dependent: 'yes',
					toolbar: 'yes',
					menubar: 'no',
					resizable: 'yes',
					scrollbars: 'yes',
					status: 'no'
				}, {
					plugin_url : url
				});
			});
			ed.addCommand('dcWebMedia', function() {
				ed.windowManager.open({
					file: popup_web_media_url,
					width: 820,
					height: 700,
					inline: 1,
					popup_css : false,
					dc_popup: '',
					alwaysRaised: 'yes',
					dependent: 'yes',
					toolbar: 'yes',
					menubar: 'no',
					resizable: 'yes',
					scrollbars: 'yes',
					status: 'no'
				}, {
					plugin_url : url
				});
			});
			
			// Register buttons
			ed.addButton('inlinecode', {
				title: 'dcControls.inlinecode_desc',
				cmd: 'dcCode'
			});
			ed.addButton('quote', {
				title: 'dcControls.quote_desc',
				cmd: 'dcQuote'
			});
			ed.addButton('link', {
				title: 'advanced.link_desc',
				cmd: 'dcLink'
			});
			ed.addButton('media', {
				title: 'dcControls.media_desc',
				cmd: 'dcMedia'
			});
			ed.addButton('webmedia', {
				title: 'dcControls.webmedia_desc',
				cmd: 'dcWebMedia'
			});
			
			// Register shortcuts
			ed.addShortcut('alt+shift+c', 'dcControls.inlinecode_desc', 'dcCode');
			ed.addShortcut('alt+shift+q', 'dcControls.quote_desc', 'dcQuote');
			ed.addShortcut('alt+shift+l', 'dcControls.link_desc', 'dcLink');
			ed.addShortcut('alt+shift+i', 'dcControls.imglink_desc', 'dcImgLink');
			ed.addShortcut('alt+shift+m', 'dcControls.media_desc', 'dcMedia');
			ed.addShortcut('alt+shift+w', 'dcControls.media_desc', 'dcWebMedia');
			
			// Register changes management
			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setActive('inlinecode', n.nodeName == 'CODE' || ed.dom.getParent(n,'CODE'));
				cm.setActive('quote', n.nodeName == 'Q' || ed.dom.getParent(n,'Q'));
			});
		},
		
		getInfo: function() {
			return {
				longname: 'Dotclear custom controls',
				author: 'Tomtom for dotclear',
				authorurl: 'http://dotclear.org',
				infourl: '',
				version: tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});
	
	// Register plugin
	tinymce.PluginManager.add('dcControls',tinymce.plugins.dcControls);
})();