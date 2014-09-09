(function() {
	CKEDITOR.plugins.add('entrylink', {
		icons: 'entrylink',
		init: function(editor) {
			editor.addCommand('entryLinkCommand', {
				exec: function(editor) {
					if (editor.getSelection().getNative().toString().replace(/\s*/,'')!='') {
						$.toolbarPopup('popup_posts.php?popup=1&plugin_id=dcCKEditor');
					}
				}
			});

			editor.ui.addButton('EntryLink', {
				label: dotclear.msg.post_link_title,
				command: 'entryLinkCommand',
				toolbar: 'insert'
			});

			editor.on('doubleclick',function(e) {
				var element = CKEDITOR.plugins.link.getSelectedLink(editor) || e.data.element;
				if (!element.isReadOnly()) {
					if (element.is('a')
					    && !element.hasClass('media-link')
					    && element.hasClass('post')) { // link to original media @see js/popup_media.js
						editor.getSelection().selectElement(element);

						$.toolbarPopup('popup_posts.php?popup=1&plugin_id=dcCKEditor');
						return false;
					}
				}
			});
		}
	});
})();
