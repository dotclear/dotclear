var dotclear = {};
dotclear.img_plus_src = '../../admin/images/expand.png';
dotclear.img_plus_alt = 'uncover';
dotclear.img_minus_src = '../../admin/images/hide.png';
dotclear.img_minus_alt = 'hide';

jQuery.fn.toggleWithLegend = function(target,s) {
	var defaults = {
		img_on_src: dotclear.img_plus_src,
		img_on_alt: dotclear.img_plus_alt,
		img_off_src: dotclear.img_minus_src,
		img_off_alt: dotclear.img_minus_alt,
		hide: true,
		speed: 0,
		legend_click: false,
		fn: false, // A function called on first display,
		cookie: false,
		reverse_cookie: false // Reverse cookie behavior
	};
	var p = jQuery.extend(defaults,s);

	if (!target) { return this; }

	var set_cookie = p.hide ^ p.reverse_cookie;
	if (p.cookie && jQuery.cookie(p.cookie)) {
		p.hide = p.reverse_cookie;
	}

	var toggle = function(i,speed) {
		speed = speed || 0;
		if (p.hide) {
			$(i).get(0).src = p.img_on_src;
			$(i).get(0).alt = p.img_on_alt;
			target.hide(speed);
		} else {
			$(i).get(0).src = p.img_off_src;
			$(i).get(0).alt = p.img_off_alt;
			target.show(speed);
			if (p.fn) {
				p.fn.apply(target);
				p.fn = false;
			}
		}

		if (p.cookie && set_cookie) {
			if (p.hide ^ p.reverse_cookie) {
				jQuery.cookie(p.cookie,'',{expires: -1});
			} else {
				jQuery.cookie(p.cookie,1,{expires: 30});
			}
		}

		p.hide = !p.hide;
	};

	return this.each(function() {
		var i = document.createElement('img');
		i.src = p.img_off_src;
		i.alt = p.img_off_alt;
		var a = document.createElement('a');
		a.href= '#';
		$(a).append(i);
		$(a).css({
			border: 'none',
			outline: 'none'
		});

		var ctarget = p.legend_click ? this : a;

		$(ctarget).css('cursor','pointer');
		if (p.legend_click) {
			$(ctarget).find('label').css('cursor','pointer');
		}
		$(ctarget).click(function() {
			toggle(i,p.speed);
			return false;
		});


		toggle($(i).get(0));
		$(this).prepend(' ').prepend(a);
	});
};
