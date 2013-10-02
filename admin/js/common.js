/* ChainHandler, py Peter van der Beken
-------------------------------------------------------- */
function chainHandler(obj, handlerName, handler) {
	obj[handlerName] = (function(existingFunction) {
		return function() {
			handler.apply(this, arguments);
			if (existingFunction)
				existingFunction.apply(this, arguments);
		};
	})(handlerName in obj ? obj[handlerName] : null);
};

/* jQuery extensions
-------------------------------------------------------- */
jQuery.fn.check = function() {
	return this.each(function() {
		if (this.checked != undefined) { this.checked = true; }
	});
};
jQuery.fn.unCheck = function() {
	return this.each(function() {
		if (this.checked != undefined) { this.checked = false; }
	});
};
jQuery.fn.setChecked = function(status) {
	return this.each(function() {
		if (this.checked != undefined) { this.checked = status; }
	});
};
jQuery.fn.toggleCheck = function() {
	return this.each(function() {
		if (this.checked != undefined) { this.checked = !this.checked; }
	});
};

jQuery.fn.enableShiftClick = function() {
	this.click(
	function (event) {
		if (event.shiftKey) {
			if (dotclear.lastclicked != '') {
				var range;
				var trparent = $(this).parents('tr');
				if (trparent.nextAll('#'+dotclear.lastclicked).length != 0)
					range = trparent.nextUntil('#'+dotclear.lastclicked);
				else
					range = trparent.prevUntil('#'+dotclear.lastclicked);

				range.find('input[type=checkbox]').setChecked(dotclear.lastclickedstatus);
				this.checked = dotclear.lastclickedstatus;
			}
		} else {
			dotclear.lastclicked = $(this).parents('tr')[0].id;
			dotclear.lastclickedstatus = this.checked;
		}
		return true;
	});
}

jQuery.fn.toggleWithLegend = function(target,s) {
	var defaults = {
		img_on_src: dotclear.img_plus_src,
		img_on_alt: dotclear.img_plus_alt,
		img_off_src: dotclear.img_minus_src,
		img_off_alt: dotclear.img_minus_alt,
		unfolded_sections: dotclear.unfolded_sections,
		hide: true,
		speed: 0,
		legend_click: false,
		fn: false, // A function called on first display,
		user_pref: false,
		reverse_user_pref: false // Reverse cookie behavior
	};
	var p = jQuery.extend(defaults,s);

	if (!target) { return this; }

	var set_cookie = p.hide ^ p.reverse_cookie;
	if (p.cookie && jQuery.cookie(p.cookie)) {
		p.hide = p.reverse_cookie;
	}
	
	var set_user_pref = p.hide ^ p.reverse_user_pref;
	if (p.user_pref && p.unfolded_sections !== undefined && (p.user_pref in p.unfolded_sections)) {
		p.hide = p.reverse_user_pref;
	}
	var toggle = function(i,speed) {
		speed = speed || 0;
		if (p.hide) {
			$(i).get(0).src = p.img_on_src;
			$(i).get(0).alt = p.img_on_alt;
			target.addClass('hide');
		} else {
			$(i).get(0).src = p.img_off_src;
			$(i).get(0).alt = p.img_off_alt;
			target.removeClass('hide');
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
			if (p.user_pref && set_user_pref) {
				if (p.hide ^ p.reverse_user_pref) {
					jQuery.post('services.php', 
						{'f':'setSectionFold','section':p.user_pref,'value':1,xd_check: dotclear.nonce},
						function(data) {});
				} else {
					jQuery.post('services.php', 
						{'f':'setSectionFold','section':p.user_pref,'value':0,xd_check: dotclear.nonce},
						function(data) {});
				}
				jQuery.cookie(p.user_pref,'',{expires: -1});
			}
			toggle(i,p.speed);
			return false;
		});


		toggle($(i).get(0));
		$(this).prepend(' ').prepend(a);
	});
};

jQuery.fn.helpViewer = function() {
	if (this.length < 1) {
		return this;
	}

	var p = {
		img_on_src: dotclear.img_plus_src,
		img_on_alt: dotclear.img_plus_alt,
		img_off_src: dotclear.img_minus_src,
		img_off_alt: dotclear.img_minus_alt
	};
	var This = this;
	var toggle = function() {
		$('#content').toggleClass('with-help');
		if (document.all) {
			if ($('#content').hasClass('with-help')) {
				select = $('#content select:visible').hide();
			} else {
				select.show();
			}
		}
		$('p#help-button span').text($('#content').hasClass('with-help') ? dotclear.msg.help_hide : dotclear.msg.help);
		sizeBox();
		return false;
	};

	var sizeBox = function() {
		This.css('height','auto');
		if ($('#wrapper').height() > This.height()) {
			This.css('height',$('#wrapper').height() + 'px');
		}
	};

	var textToggler = function(o) {
		var i = $('<img src="'+p.img_on_src+'" alt="'+p.img_on_alt+'" />');
		o.css('cursor','pointer');
		var hide = true;

		o.prepend(' ').prepend(i);
		o.click(function() {
			$(this).nextAll().each(function() {
				if ($(this).is('h4')) {
					return false;
				}
				$(this).toggle();
				sizeBox();
				return true;
			});
			hide = !hide;
			var img = $(this).find('img');
			if (!hide) {
				img.attr('src',p.img_off_src);
			} else {
				img.attr('src',p.img_on_src);
			}
		});
	};

	this.addClass('help-box');
	this.find('>hr').remove();

	this.find('h4').each(function() { textToggler($(this)); });
	this.find('h4:first').nextAll('*:not(h4)').hide();
	sizeBox();

	var img = $('<p id="help-button"><span>'+dotclear.msg.help+'</span></p>');
	var select = $();
	img.click(function() { return toggle(); });

	$('#content').append(img);
	
	// listen for scroll
	var peInPage = $('#help-button').offset().top;
	$('#help-button').addClass("floatable");
	var peInFloat = $('#help-button').offset().top;
	$('#help-button').removeClass("floatable");
	$(window).scroll(
		function() {
			if ($(window).scrollTop() >= peInPage - peInFloat ) {
				$('#help-button').addClass("floatable");
			} else {
				$('#help-button').removeClass("floatable");
			}
		}
	);

	return this;
};

/* Dotclear common object
-------------------------------------------------------- */
var dotclear = {
	msg: {},

	hideLockable: function() {
		$('div.lockable').each(function() {
			var current_lockable_div = this;
			$(this).find('p.form-note').hide();
			$(this).find('input').each(function() {
				this.disabled = true;
				$(this).width(($(this).width()-14) + 'px');

				var imgE = document.createElement('img');
				imgE.src = 'images/locker.png';
				imgE.style.position = 'absolute';
				imgE.style.top = '1.7em';
				imgE.style.left = ($(this).width()+12)+'px';
				imgE.alt=dotclear.msg.click_to_unlock;
				$(imgE).css('cursor','pointer');

				$(imgE).click(function() {
					$(this).hide();
					$(this).prev('input').each(function() {
						this.disabled = false;
						$(this).width(($(this).width()+14) + 'px');
					});
					$(current_lockable_div).find('p.form-note').show();
				});

				$(this).parent().css('position','relative');
				$(this).after(imgE);
			});
		});
	},

	checkboxesHelpers: function(e, target) {
		$(e).append(document.createTextNode(dotclear.msg.to_select));
		$(e).append(document.createTextNode(' '));

		$('<a href="#">'+dotclear.msg.select_all+'</a>').click(function() {
			if (target !== undefined) {
				target.check();
			} else {
				$(e).parents('form').find('input[type="checkbox"]').check();
			}

			return false;
		}).appendTo($(e));
		$(e).append(document.createTextNode(' | '));

		$('<a href="#">'+dotclear.msg.no_selection+'</a>').click(function() {
			if (target !== undefined) {
				target.unCheck();
			} else {
				$(e).parents('form').find('input[type="checkbox"]').unCheck();
			}

			return false;
		}).appendTo($(e));
		$(e).append(document.createTextNode(' - '));

		$('<a href="#">'+dotclear.msg.invert_sel+'</a>').click(function() {
			if (target !== undefined) {
				target.toggleCheck();
			} else {
				$(e).parents('form').find('input[type="checkbox"]').toggleCheck();
			}

			return false;
		}).appendTo($(e));
	},

	postsActionsHelper: function() {
		$('#form-entries').submit(function() {
			var action = $(this).find('select[name="action"]').val();
			var checked = false;

			$(this).find('input[name="entries[]"]').each(function() {
				if (this.checked) {
					checked = true;
				}
			});

			if (!checked) { return false; }

			if (action == 'delete') {
				return window.confirm(dotclear.msg.confirm_delete_posts.replace('%s',$('input[name="entries[]"]:checked').size()));
			}

			return true;
		});
	},

	commentsActionsHelper: function() {
		$('#form-comments').submit(function() {
			var action = $(this).find('select[name="action"]').val();
			var checked = false;

			$(this).find('input[name="comments[]"]').each(function() {
				if (this.checked) {
					checked = true;
				}
			});

			if (!checked) { return false; }

			if (action == 'delete') {
				return window.confirm(dotclear.msg.confirm_delete_comments.replace('%s',$('input[name="comments[]"]:checked').size()));
			}

			return true;
		});
	},
	getCSSColor : function ( clazz) {
		$('<div class="'+clazz+'" id="dotclear-obj-test-color" style="display:none"></div>').appendTo(document.body);
		var tag2 = $('#dotclear-obj-test-color');
		var color = $.trim(tag2.css("color").toLowerCase());
		tag2.remove();
		if ( color.charAt(0) === '#') {
			return color;
		}
		var result = /^rgb\((\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\)$/.exec(color);		
		if ( result === null) {
			return '';
		}
		var ret = '#';
		for ( var i = 1; i < 4; i++) {
			var val = parseInt(result[i],10);
			ret += (val < 16 ? '0'+val.toString(16) : val.toString(16));
		}
		return ret;
	},
	initFadeColor : function() {
		dotclear.fadeColor = {
			beginPassword : dotclear.getCSSColor('colorBeginPassword'),
			endPassword : dotclear.getCSSColor('colorEndPassword'),
			beginMessage : dotclear.getCSSColor('colorBeginMessage'),
			endMessage : dotclear.getCSSColor('colorEndMessage'),
			beginError : dotclear.getCSSColor('colorBeginError'),
			endError : dotclear.getCSSColor('colorEndError'),
			beginSuccess : dotclear.getCSSColor('colorBeginSuccess'),
			endSuccess : dotclear.getCSSColor('colorEndSuccess'),
			beginValidatorMsg : dotclear.getCSSColor('colorBeginValidatorMsg'),
			endValidatorMsg : dotclear.getCSSColor('colorEndValidatorMsg'),
			beginValidatorErr : dotclear.getCSSColor('colorBeginValidatorErr'),
			endValidatorErr : dotclear.getCSSColor('colorEndValidatorErr'),
			beginUserMail : dotclear.getCSSColor('colorBeginUserMail'),
			endUserMail : dotclear.getCSSColor('colorEndUserMail')
		};
	}};

/* On document ready
-------------------------------------------------------- */
$(function() {
	dotclear.initFadeColor();
	// remove class no-js from html tag; cf style/default.css for examples
	$('body').removeClass('no-js').addClass('with-js');
	
	$('#wrapper').contents().each(function() {
		if (this.nodeType==8) {
			$('#footer a[href!="help.php"]').attr('title', $('#footer a[href!="help.php"]').attr('title') + this.data );
		}
	});

	// Blog switcher
	$('#switchblog').change(function() {
		this.form.submit();
	});

	var menu_settings = {
		img_on_src: dotclear.img_menu_off,
		img_off_src: dotclear.img_menu_on,
		legend_click: true,
		speed: 100
	}
	$('#blog-menu h3:first').toggleWithLegend($('#blog-menu ul:first'),
		$.extend({user_pref:'dc_blog_menu'},menu_settings)
	);
	$('#system-menu h3:first').toggleWithLegend($('#system-menu ul:first'),
		$.extend({user_pref:'dc_system_menu'},menu_settings)
	);
	$('#plugins-menu h3:first').toggleWithLegend($('#plugins-menu ul:first'),
		$.extend({user_pref:'dc_plugins_menu'},menu_settings)
	);
	$('#favorites-menu h3:first').toggleWithLegend($('#favorites-menu ul:first'),
		$.extend({user_pref:'dc_favorites_menu',hide:false,reverse_user_pref:true},menu_settings)
	);

	$('#help').helpViewer();

	$('.message').backgroundFade({sColor: dotclear.fadeColor.beginMessage, eColor: dotclear.fadeColor.endMessage, steps:20});
	$('.error').backgroundFade({sColor: dotclear.fadeColor.beginError, eColor: dotclear.fadeColor.endError, steps:20});
	$('.success').backgroundFade({sColor: dotclear.fadeColor.beginSuccess, eColor: dotclear.fadeColor.endSuccess, steps:20});

	$('form:has(input[type=password][name=your_pwd])').submit(function() {
		var e = this.elements['your_pwd'];
		if (e.value == '') {
			e.focus();
			$(e).backgroundFade({sColor: dotclear.fadeColor.beginPassword,eColor: dotclear.fadeColor.endPassword,steps:50},function() {
				$(this).backgroundFade({sColor: dotclear.fadeColor.endPassword,eColor: dotclear.fadeColor.beginPassword});
			});
			return false;
		}
		return true;
	});

	// Main menu collapser
    var objMain = $('#wrapper');
    function showSidebar(){
	    // Show sidebar
        objMain.removeClass('hide-mm');
        $.cookie('sidebar-pref',null,{expires:30});
    }
    function hideSidebar(){
	    // Hide sidebar
        objMain.addClass('hide-mm');
        $.cookie('sidebar-pref','hide-mm',{expires:30});
    }
    // Sidebar separator
    var objSeparator = $('#collapser');
    objSeparator.click(function(e){
        e.preventDefault();
        if ( objMain.hasClass('hide-mm') ){
            showSidebar();
        }
        else {
            hideSidebar();
        }
    });
	if ( $.cookie('sidebar-pref') == 'hide-mm' ){
		objMain.addClass('hide-mm');
	} else {
		objMain.removeClass('hide-mm');
	}
});
