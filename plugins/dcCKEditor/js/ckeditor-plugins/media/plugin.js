(function() {
	CKEDITOR.plugins.add('media', {
		icons: 'media',
		init: function(editor) {
			editor.addCommand('mediaCommand', {
				exec: function(editor) {
					$.toolbarPopup('media.php?popup=1&plugin_id=dcCKEditor');
				}
			});

			editor.ui.addButton('Media', {
				label: dotclear.msg.img_select_title,
				command: 'mediaCommand',
				toolbar: 'insert'
			});

			editor.on('doubleclick',function(e) {
				var element = CKEDITOR.plugins.link.getSelectedLink(editor) || e.data.element;
				if (!element.isReadOnly()) {
					if (element.is('img') || (element.is('a') && element.hasClass('media-link'))) {
						$.toolbarPopup('media.php?popup=1&plugin_id=dcCKEditor');
						return false;
					}
				}
			});
		}
	});
})();

