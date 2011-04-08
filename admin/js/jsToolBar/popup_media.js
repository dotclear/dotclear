$(function() {
	var toolBar = window.opener.the_toolbar.textarea;
	
	$('#media-insert').onetabload(function() {
		$('#media-insert-cancel').click(function() {
			window.close();
		});
		
		$('#media-insert-ok').click(function() {
			sendClose();
			window.close();
		});
	});
	
	function sendClose() {
		var insert_form = $('#media-insert-form').get(0);
		if (insert_form == undefined) { return; }
		
		var tb = window.opener.the_toolbar;
		var type = insert_form.elements.type.value;
		
		var media_align_grid = {
			left: 'float: left; margin: 0 1em 1em 0;',
			right: 'float: right; margin: 0 0 1em 1em;',
			center: 'text-align: center;'
		};
		
		if (type == 'image')
		{
			tb.elements.img_select.data.src = tb.stripBaseURL($('input[name="src"]:checked',insert_form).val());
			tb.elements.img_select.data.alignment = $('input[name="alignment"]:checked',insert_form).val();
			tb.elements.img_select.data.link = $('input[name="insertion"]:checked',insert_form).val() == 'link';
			
			tb.elements.img_select.data.title = insert_form.elements.title.value;
			tb.elements.img_select.data.description = $('input[name="description"]',insert_form).val();
			tb.elements.img_select.data.url = tb.stripBaseURL(insert_form.elements.url.value);
			tb.elements.img_select.fncall[tb.mode].call(tb);
		}
		else if (type == 'mp3')
		{
			var player = $('#public_player').val();
			var align = $('input[name="alignment"]:checked',insert_form).val();
			
			if (align != undefined && align != 'none') {
				player = '<div style="' + media_align_grid[align] + '">' + player + '</div>';
			}
			
			tb.elements.mp3_insert.data.player = player.replace(/>/g,'>\n');
			tb.elements.mp3_insert.fncall[tb.mode].call(tb);
		}
		else if (type == 'flv')
		{
			var oplayer = $('<div>'+$('#public_player').val()+'</div>');
			var flashvars = $("[name=FlashVars]",oplayer).val();
			
			var align = $('input[name="alignment"]:checked',insert_form).val();
			var title = insert_form.elements.title.value;
			
			if (title) {
				flashvars = 'title='+encodeURI(title)+'&amp;'+flashvars;
			}
			$('object',oplayer).attr('width',$('#video_w').val());
			$('object',oplayer).attr('height',$('#video_h').val());
			flashvars = flashvars.replace(/(width=\d*)/,'width='+$('#video_w').val());
			flashvars = flashvars.replace(/(height=\d*)/,'height='+$('#video_h').val());
			
			$("[name=FlashVars]",oplayer).val(flashvars);
			var player = oplayer.html();	
			
			if (align != undefined && align != 'none') {
				player = '<div style="' + media_align_grid[align] + '">' + player + '</div>';
			}
			
			tb.elements.flv_insert.data.player = player.replace(/>/g,'>\n');
			tb.elements.flv_insert.fncall[tb.mode].call(tb);
		}
		else
		{
			tb.elements.link.data.href = tb.stripBaseURL(insert_form.elements.url.value);
			tb.elements.link.data.content = insert_form.elements.title.value;
			tb.elements.link.fncall[tb.mode].call(tb);
		}
	};
	
	function playerFormat(s) {
		s = s.replace(/&lt;/g,'<');
		s = s.replace(/&gt;/g,'>\n');
		s = s.replace(/&amp;/g,'&');
		
		return s;
	};
});