$(function() {
	var f = $('#quick-entry');
	if (f.length > 0) {
		var contentTb = new jsToolBar($('#post_content',f)[0]);
		contentTb.switchMode($('#post_format',f).val());
		
		$('input[name=save]',f).click(function() {
			quickPost(f,-2);
			return false;
		});
		
		if ($('input[name=save-publish]',f).length > 0) {
			var btn = $('<input type="submit" class="submit" value="' + $('input[name=save-publish]',f).val() + '" tabindex="3" />');
			$('input[name=save-publish]',f).remove();
			$('input[name=save]',f).after(btn).after(' ');
			btn.click(function() {
				quickPost(f,1);
				return false;
			});
		}
		
		function quickPost(f,status) {
			if (contentTb.getMode() == 'wysiwyg') {
				contentTb.syncContents('iframe');
			}
			
			var params = {
				f: 'quickPost',
				xd_check: dotclear.nonce,
				post_title: $('#post_title',f).val(),
				post_content: $('#post_content',f).val(),
				cat_id: $('#cat_id',f).val(),
				post_status: status,
				post_format: $('#post_format',f).val(),
				post_lang: $('#post_lang',f).val()
			}
			
			$('p.qinfo',f).remove();
			
			$.post('services.php',params,function(data) {
				if ($('rsp[status=failed]',data).length > 0) {
					var msg = '<p class="qinfo"><strong>' + dotclear.msg.error +
					'</strong> ' + $('rsp',data).text() + '</p>';
				} else {
					var msg = '<p class="qinfo">' + dotclear.msg.entry_created +
					' - <a href="post.php?id=' + $('rsp>post',data).attr('id') + '">' +
					dotclear.msg.edit_entry + '</a>';
					if ($('rsp>post',data).attr('post_status') == 1) {
						msg += ' - <a href="' + $('rsp>post',data).attr('post_url') + '">' +
						dotclear.msg.view_entry + '</a>';
					}
					msg += '</p>';
					$('#post_title',f).val('');
					$('#post_content',f).val('');
					if (contentTb.getMode() == 'wysiwyg') {
						contentTb.syncContents('textarea');
					}
				}
				
				$('fieldset',f).prepend(msg);
			});
		}
	}
	
	// allow to hide quick entry div, and remember choice
	$('#quick h3').toggleWithLegend($('#quick').children().not('h3'),{
		cookie: 'dcx_quick_entry',
	});
});