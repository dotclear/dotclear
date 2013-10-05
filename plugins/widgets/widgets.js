/*
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
*/

dotclear.postExpander = function(line) {
	var title = $(line).find('.widget-name');
	title.find('.form-note').remove();
	order = title.find('input[name*=order]');
	link = $('<a href="#" alt="expand" class="aexpand"/>').append(title.text());
	rem = title.find('input[name*=_rem]');
	br = title.find('br');
	title.empty().append(order).append(link).append(rem).append(br);
	
	var img = document.createElement('img');
	img.src = dotclear.img_plus_src;
	img.alt = dotclear.img_plus_alt;
	img.className = 'expand';
	$(img).css('cursor','pointer');
	img.onclick = function() { dotclear.viewPostContent($(this).parents('li')); };
	link.click(function(e) {
		e.preventDefault();
		dotclear.viewPostContent($(this).parents('li'));
	});
	
	title.prepend(img);
};

dotclear.viewPostContent = function(line,action) {
	var action = action || 'toogle';
	var img = line.find('.expand');
	var isopen = img.attr('alt') == dotclear.img_plus_alt;
	
	if( action == 'close' || ( action == 'toogle' && !isopen ) ) {
		line.find('.widgetSettings').hide();
		img.attr('src', dotclear.img_plus_src);
		img.attr('alt', dotclear.img_plus_alt);
	} else if ( action == 'open' || ( action == 'toogle' && isopen ) ) {
		line.find('.widgetSettings').show();
		img.attr('src', dotclear.img_minus_src);
		img.attr('alt', dotclear.img_minus_alt);
	}
	
};

$(function() {	
	// reset
	$('input[name="wreset"]').click(function() {
		return window.confirm(dotclear.msg.confirm_widgets_reset);
	});
	
	// plier/déplier
	$('#dndnav > li, #dndextra > li, #dndcustom > li').each(function() {
		dotclear.postExpander(this);
		dotclear.viewPostContent($(this), 'close');
	});
	
	// remove
	$('input[name*=rem]').change(function () {
	    if ($(this).attr("checked")) {
	        $(this).parents('li').remove();
	    }
	});
	
});