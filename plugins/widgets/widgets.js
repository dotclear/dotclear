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
	tools = title.find('.toolsWidget');
	br = title.find('br');
	title.empty().append(order).append(link).append(tools).append(br);
	
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

function reorder(ul) {
	// réordonne
	if( ul.attr('id') ) {
		$list = ul.find('li').not('.empty-widgets');
		$list.each(function(i) {
			$this = $(this);
			
			// trouve la zone de réception
			var name = ul.attr('id').split('dnd').join('');
			
			// modifie le name en conséquence
			$this.find('*[name^=w]').each(function(){
				tab = $(this).attr('name').split('][');
				tab[0] = "w["+name;
				tab[1] = i;
				$(this).attr('name', tab.join(']['));
			});
			
			// ainsi que le champ d'ordre sans js (au cas ou)
			$this.find('input[title=ordre]').val(i);
			
			// active ou désactive les fléches
			if( i == 0 ) {
				$this.find('input.upWidget').prop('disabled', true);
				$this.find('input.upWidget').prop('src', 'images/disabled_up.png' );
			} else {
				$this.find('input.upWidget').removeAttr('disabled');
				$this.find('input.upWidget').prop('src', 'images/up.png' );
			}
			if( i == $list.length-1 ) {
				$this.find('input.downWidget').prop('disabled', true);
				$this.find('input.downWidget').prop('src', 'images/disabled_down.png' );
			} else {
				$this.find('input.downWidget').removeAttr('disabled');
				$this.find('input.downWidget').prop('src', 'images/down.png' );
			}
			
		});
	}
}

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
	$('input[name*=rem]').click(function (e) {
		e.preventDefault();
		$(this).parents('li').remove();
	});
	
	// move
	$('input[name*=down]').click(function (e) {
		e.preventDefault();
		$this = $(this);
		$li = $this.parents('li');
		$li.next().after($li);
		reorder($this.parents('ul.connected'));
	});
	$('input[name*=up]').click(function (e) {
		e.preventDefault();
		$this = $(this);
		$li = $this.parents('li');
		$li.prev().before($li);
		reorder($this.parents('ul.connected'));
	});
});