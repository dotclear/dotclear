dotclear.postExpander = function(line) {
	var title = $(line).find('.widget-name');
	
	var img = document.createElement('img');
	img.src = dotclear.img_plus_src;
	img.alt = dotclear.img_plus_alt;
	img.className = 'expand';
	$(img).css('cursor','pointer');
	img.line = line;
	img.onclick = function() { dotclear.viewPostContent(this.line); };
	
	title.prepend(img);
};

dotclear.viewPostContent = function(line,action) {
	var action = action || 'toogle';
	var img = $(line).find('.expand');
	var isopen = img.attr('alt') == dotclear.img_plus_alt;
	
	if( action == 'close' || ( action == 'toogle' && !isopen ) ) {
		$(line).find('.widgetSettings').hide();
		img.attr('src', dotclear.img_plus_src);
		img.attr('alt', dotclear.img_plus_alt);
	} else if ( action == 'open' || ( action == 'toogle' && isopen ) ) {
		$(line).find('.widgetSettings').show();
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
		dotclear.viewPostContent(this, 'close');
	});
	
	// remove
	$('input[name*=rem]').change(function () {
	    if ($(this).attr("checked")) {
	        $(this).parents('li').remove();
	    }
	});
	
});