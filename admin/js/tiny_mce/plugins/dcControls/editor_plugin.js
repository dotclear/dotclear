(function() {
	tinymce.create('tinymce.plugins.dcControls', {
		// Init.
		init : function(ed, url) {
			var t = this;
			var popup_link_url = 'popup_link.php';
			var popup_post_url = 'popup_posts.php';
			var popup_media_url = 'media.php?popup=1';
			
			t.editor = ed;
			
			// Register commands
			ed.addCommand('dcCode', function() {
				ed.formatter.toggle('inlinecode');
			});
			ed.addCommand('dcQuote', function() {
				ed.formatter.toggle('quote');
			});
			ed.addCommand('dcExternalLink', function() {
				var se = ed.selection;
				
				// No selection and not in link
				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'A'))
					return;
				
				var url = popup_link_url;
				var node = se.getNode();
				
				if (node.nodeName  == 'A') {
					var href= node.href || '';
					var title = node.title || '';
					var hreflang = node.hreflang || '';
					url += '?href='+href+'&hreflang='+hreflang+'&title='+title;
				}
				ed.windowManager.open({
					file: url,
					width: 520,
					height: 420,
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
			ed.addCommand('dcPostLink', function() {
				var se = ed.selection;
				
				// No selection and not in link
				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'A'))
					return;
				
				ed.windowManager.open({
					file: popup_post_url,
					width: 760,
					height: 500,
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
			ed.addCommand('dcMediaLink', function() {
				ed.windowManager.open({
					file: popup_media_url,
					width: 760,
					height: 500,
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
				cmd: 'dcCode',
				image: url + '/img/code.png'
			});
			ed.addButton('quote', {
				title: 'dcControls.quote_desc',
				cmd: 'dcQuote',
				image: url + '/img/quote.png'
			});
			ed.addButton('link', {
				title: 'dcControls.link_desc',
				cmd: 'dcExternalLink'
			});
			ed.addButton('postlink', {
				title: 'dcControls.postlink_desc',
				cmd: 'dcPostLink',
				image: url + '/img/postlink.png'
			});
			ed.addButton('medialink', {
				title: 'dcControls.medialink_desc',
				cmd: 'dcMediaLink',
				image: url + '/img/medialink.png'
			});
			
			// Register shortcuts
			ed.addShortcut('ctrl+alt+q', 'dcControls.quote_desc', 'dcQuote');
			ed.addShortcut('ctrl+alt+l', 'dcControls.link_desc', 'dcExternalLink');
			ed.addShortcut('ctrl+alt+p', 'dcControls.postlink_desc', 'dcPostLink');
			ed.addShortcut('ctrl+alt+m', 'dcControls.medialink_desc', 'dcMediaLink');
			
			// Register changes management
			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setDisabled('postlink', co && n.nodeName != 'A');
				cm.setActive('inlinecode', n.nodeName == 'CODE' || ed.dom.getParent(n, 'CODE'));
				cm.setActive('quote', n.nodeName == 'Q' || ed.dom.getParent(n, 'Q'));
				cm.setActive('postlink', n.nodeName == 'A' && !n.name);
			});
		},
		
		getInfo : function() {
			return {
				longname : 'Dotclear custom controls',
				author : 'Tomtom for dotclear',
				authorurl : 'http://dotclear.org',
				infourl : '',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});
	
	// Register plugin
	tinymce.PluginManager.add('dcControls', tinymce.plugins.dcControls);
})();