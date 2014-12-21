(function() {
	CKEDITOR.plugins.add('external-media', {
		init: function(editor) {
			editor.addCommand('dcExternalMediaCommand', new CKEDITOR.dialogCommand('externalMediaDialog'));

			CKEDITOR.dialog.add('externalMediaDialog', this.path+'dialogs/external-media.js');

			editor.ui.addButton('dcExternalMedia', {
				label: dotclear.msg.external_media_title,
				command: 'dcExternalMediaCommand',
				toolbar: 'insert',
				icon: this.path + 'icons/external-media.png'
			});
		}
	});
})();
