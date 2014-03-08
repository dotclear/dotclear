(function() {
	CKEDITOR.plugins.add('dctags', {
		init: function(editor) {
			editor.addCommand('dcTagsCommand', {
				exec: function(editor) {
					if (editor.getSelection().getNative().toString().replace(/\s*/,'')!='') {
						var str = editor.getSelection().getNative().toString().replace(/\s*/,'');
						var url = jsToolBar.prototype.elements.tag.url;
						window.dc_tag_editor.addMeta(str);
						var link = '<a href="'+$.stripBaseURL(url+'/'+str)+'">'+str+'</a>';
						var element = CKEDITOR.dom.element.createFromHtml(link);
						editor.insertElement(element);
					}
				}
			});

			editor.ui.addButton('dcTags', {
				label: 'Tag',
				command: 'dcTagsCommand',
				toolbar: 'insert',
				icon: this.path + 'tag.png'
			});
		}
	});
})();
