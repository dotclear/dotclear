$(function() {
	$('#themes-actions').hide();
	var submit_s = $('#themes-actions input[name=select]');
	var submit_r = $('#themes-actions input[name=remove]');
	
	var details = $('#themes div.theme-details');
	$('div.theme-actions',details).hide();
	$('input:radio',details).hide();
	$('div.theme-info span, div.theme-info a',details).hide();
	details.removeClass('theme-details').addClass('theme-details-js');
	
	var themes_wrapper = $('<div id="themes-wrapper"></div>');
	var theme_box = $('<div id="theme-box"><div</div>');
	$('#themes').wrap(themes_wrapper).before(theme_box);
	
	details.each(function() {
		var box = this;
		var a = $(document.createElement('a'));
		a.attr('href','#');
		a.attr('title',$('>div h3>label',this).text());
		$(box).wrap(a);
		$(box).parent().click(function(event) {
			update_box(box);
			event.preventDefault();
			return false;
		});
	});
	
	function update_box(e) {
		theme_box.empty();
		var img = $('div.theme-shot',e).clone();
		var info = $('div.theme-info',e).clone();
		
		if ($(e).hasClass('current-theme')) {
			var actions = $('div.theme-actions',e).clone();
			actions.show();
		} else {
			var actions = $('<div class="theme-actions"></div>');
			if (submit_s.length > 0  && !$('input:radio',info).attr('disabled')) {
				var select = $('<a href="#" class="button">' + dotclear.msg.use_this_theme + '</a>');
				select.css('font-weight','bold').click(function() {
					submit_s.click();
					return false;
				});
				actions.append(select).append('&nbsp;&nbsp;');
			}
			if (submit_r.length > 0 && $('input:radio',info).attr('id') != 'theme_default') {
				var remove = $('<a href="#" class="button delete">' + dotclear.msg.remove_this_theme + '</a>');
				remove.click(function() {
					var t_name = $(this).parents('#theme-box').find('div.theme-info h3:first').text();
					t_name = $.trim(t_name);
					if (window.confirm(dotclear.msg.confirm_delete_theme.replace('%s',t_name))) {
						submit_r.click();
					}
					return false;
				});
				actions.append(remove);
			}
		}
		
		$('input:radio',info).remove();
		$('span, a',info).show();
		
		theme_box.append(img).append(info).append(actions);
		details.removeClass('theme-selected');
		$(e).addClass('theme-selected');
		$('input:radio',e).prop('checked',true);
	}
	
	update_box(details[0]);
});
