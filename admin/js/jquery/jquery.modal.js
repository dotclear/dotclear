(function($) {
	if (/^1\.(0|1)\./.test($.fn.jquery) || /^1\.2\.(0|1|2|3|4|5)/.test($.fn.jquery)) {
		throw('Modal requieres jQuery v1.2.6 or later. You are using v'+$.fn.jquery);
		return;
	}
	
	$.modal = function(data,params) {
		this.params = $.extend(this.params,params);
		return this.build(data);
	};
	$.modal.version = '1.0';
	
	$.modal.prototype = {
		params: {
			width: null,
			height: null,
			speed: 300,
			opacity: 0.9,
			loader_img: 'loader.gif',
			loader_txt: 'loading...',
			close_img: 'close.png',
			close_txt: 'close',
			on_close: function() {}
		},
		ctrl: {
			box: $(),
			loader: $(),
			overlay: $('<div id="jq-modal-overlay"></div>'),
			hidden: $()
		},
		
		build: function(data) {
			this.ctrl.loader = $('<div class="jq-modal-load"><img src="' + this.params.loader_img + '" alt="' + this.params.loader_txt + '" /></div>');
			this.addOverlay();
			var size = this.getBoxSize(this.ctrl.loading);
			
			this.ctrl.box = this.getBox(this.ctrl.loading,{
				top: Math.round($(window).height()/2 + $(window).scrollTop() - size.h/2),
				left: Math.round($(window).width()/2 + $(window).scrollLeft() - size.w/2),
				visibility: 'hidden'
			});
			this.ctrl.overlay.after(this.ctrl.box);
			if (data != undefined) {
				this.updateBox(data);
				this.data = data;
			}
			return this;
		},
		updateBox: function(data,fn) {
			var This = this;
			this.hideCloser();
			fn = $.isFunction(fn) ? fn : function() {};
			var content = $('div.jq-modal-content',this.ctrl.box);
			content.empty().append(this.ctrl.loader);
			var size = this.getBoxSize(data,this.params.width,this.params.height);
			
			var top = Math.round($(window).height()/2 + $(window).scrollTop() - size.h/2);
			var left = Math.round($(window).width()/2 + $(window).scrollLeft() - size.w/2);
			
			this.ctrl.box.css('visibility','visible').animate({
				top: top < 0 ? 0 : top,
				left: left < 0 ? 0 : left,
				width: size.w,
				height: size.h
			},this.params.speed,function() {
				This.setContentSize(content,This.params.width,This.params.height);
				content.empty().append(data).css('opacity',0)
				.fadeTo(This.params.speed,1,function() {
					fn.call(This,content);
				});
				This.showCloser();
			});
		},
		getBox: function(data,css,content_w,content_h) {
			var box = $(
				'<div class="jq-modal">'+
				'<div class="jq-modal-container"><div class="jq-modal-content">'+
				'</div></div></div>'
				).css($.extend({
					position: 'absolute',
					top: 0,
					left: 0,
					zIndex: 100
				},css));
			
			if (data != undefined) {
				$('div.jq-modal-content',box).append(data);
			}
			
			this.setContentSize($('div.jq-modal-content',box),content_w,content_h);
			return box;
		},
		getBoxSize: function(data,content_w,content_h) {
			var box = this.getBox(data,{ visibility: 'hidden' },content_w,content_h);
			this.ctrl.overlay.after(box);
			var size = { w: box.width(), h: box.height() };
			box.remove();
			box = null;
			return size;
		},
		setContentSize: function(content,w,h) {
			content.css({
				width: w > 0 ? w : 'auto',
				height: h > 0 ? h : 'auto'
			});
		},
		showCloser: function() {
			if ($('div.jq-modal-closer',this.ctrl.box).length > 0) {
				$('div.jq-modal-closer',this.ctrl.box).show();
				return;
			}
			
			$('div.jq-modal-container',this.ctrl.box).append(
				'<div class="jq-modal-closer"><a href="#">' + this.params.close_txt + '</a></div>'
			);
			var This = this;
			var close = $('div.jq-modal-closer a',this.ctrl.box)
			close.css({
				background: 'transparent url(' + this.params.close_img + ') no-repeat'
			})
			.click(function() {
				This.removeOverlay();
				return false;
			});
			
			if (document.all) {
				close[0].runtimeStyle.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="' + this.params.close_img + '", sizingMethod="crop")';
				close[0].runtimeStyle.backgroundImage = "none"
			}
		},
		hideCloser: function() {
			$('div.jq-modal-closer',this.ctrl.box).hide();
		},
		
		addOverlay: function() {
			var This = this;
			if (document.all) {
				this.ctrl.hidden = $('select:visible, object:visible, embed:visible').
				css('visibility','hidden');
			}
			this.ctrl.overlay.css({
				backgroundColor: '#000',
				position: 'absolute',
				top: 0,
				left: 0,
				zIndex: 90,
				opacity: this.params.opacity
			})
			.appendTo('body')
			.dblclick(function() { This.removeOverlay(); });
			
			this.resizeOverlay({data:this.ctrl});
			
			$(window).bind('resize.modal',this.ctrl,this.resizeOverlay);
			$(document).bind('keypress.modal',this,this.keyRemove);
		},
		resizeOverlay: function(e) {
			e.data.overlay.css({
				width: $(window).width(),
				height: $(document).height()
			});
			if (e.data.box.parents('body').length > 0) {
				var top = Math.round($(window).height()/2 + $(window).scrollTop() - e.data.box.height()/2);
				var left = Math.round($(window).width()/2 + $(window).scrollLeft() - e.data.box.width()/2);
				e.data.box.css({
					top: top < 0 ? 0 : top,
					left: left < 0 ? 0 : left
				});
			}
		},
		keyRemove: function(e) {
			if (e.keyCode == 27) {
				e.data.removeOverlay();
			}
			return true;
		},
		removeOverlay: function() {
			$(window).unbind('resize.modal');
			$(document).unbind('keypress');
			this.params.on_close.apply(this);
			this.ctrl.overlay.remove();
			this.ctrl.hidden.css('visibility','visible');
			this.ctrl.box.remove();
			this.ctrl.box = $();
		}
	};
})(jQuery);

(function($) {
	$.fn.modalImages = function(params) {
		params = $.extend(this.params,params);
		var links = new Array();
		this.each(function() {
			if ($(this).attr('href') == '' || $(this).attr('href') == undefined || $(this).attr('href') == '#') {
				return false;
			}
			var index = links.length;
			links.push($(this));
			$(this).click(function() {
				new $.modalImages(index,links,params);
				return false;
			});
			return true;
		});
		
		return this;
	};
	
	$.modalImages = function(index,links,params) {
		this.links = links;
		this.modal = new $.modal(null,params);
		this.showImage(index);
	};
	
	$.modalImages.prototype = {
		params: {
			prev_txt: 'previous',
			next_txt: 'next',
			prev_img: 'prev.png',
			next_img: 'next.png',
			blank_img: 'blank.gif'
		},
		showImage: function(index) {
			var This = this;
			$(document).unbind('keypress.modalImage');
			if (this.links[index] == undefined) {
				this.modal.removeOverlay();
			}
			var link = this.links[index];
			var modal = this.modal;
			
			var res = $('<div></div>');
			res.append('<img src="' + link.attr('href') + '" alt="" />');
			
			var thumb = $('img:first',link);
			if (thumb.length > 0 && thumb.attr('title')) {
				res.append('<span class="jq-modal-legend">' + thumb.attr('title') + '</span>');
			} else if (link.attr('title')) {
				res.append('<span class="jq-modal-legend">' + link.attr('title') + '</span>');
			}
			
			// Add prev/next buttons
			if (index != 0) {
				$('<a class="jq-modal-prev" href="#">prev</a>').appendTo(res);
			}
			if (index+1 < this.links.length) {
				$('<a class="jq-modal-next" href="#">next</a>').appendTo(res);
			}
			
			var img = new Image();
			
			// Display loader while loading image
			if (this.modal.ctrl.box.css('visibility') == 'visible') {
				$('div.jq-modal-content',this.modal.ctrl.box)
				.empty().append(this.modal.ctrl.loader);
			} else {
				this.modal.updateBox(this.modal.ctrl.loader);
			}
			
			img.onload = function() {
				modal.updateBox(res,function() {
					var Img = $('div.jq-modal-content img',this.ctrl.box);
					This.navBtnStyle($('a.jq-modal-next',this.ctrl.box),true).css('height',Img.height()).bind('click',index+1,navClick);
					This.navBtnStyle($('a.jq-modal-prev',this.ctrl.box),false).css('height',Img.height()).bind('click',index-1,navClick);
					Img.click(function() {
						This.modal.removeOverlay();
					});
					$(document).bind('keypress.modalImage',navKey);
				});
				this.onload = function() {};
			};
			img.src = link.attr('href');
			
			var navClick = function(e) {
				This.showImage(e.data);
				return false;
			};
			var navKey = function(e) {
				var key = String.fromCharCode(e.which).toLowerCase();
				if ((key == 'n' || e.keyCode == 39) && index+1 < This.links.length) { // Press "n"
					This.showImage(index+1);
				}
				if ((key == 'p' || e.keyCode == 37) && index != 0) { // Press "p"
					This.showImage(index-1);
				}
			};
		},
		navBtnStyle: function(btn,next) {
			var default_bg = 'transparent url(' + this.modal.params.blank_img + ') repeat';
			var over_bg_i = next ? this.modal.params.next_img : this.modal.params.prev_img;
			var over_bg_p = next ? 'right' : 'left';
			
			btn.css('background',default_bg)
			.bind('mouseenter',function() {
				$(this).css('background','transparent url(' + over_bg_i + ') no-repeat center ' + over_bg_p).css('z-index',110);
			})
			.bind('mouseleave',function() {
				$(this).css('background',default_bg);
			});
			
			return btn;
		}
	};
})(jQuery);

(function($) {
	$.modalWeb = function(url,w,h) {
		iframe = $('<iframe src="' + url + '" frameborder="0">').css({
			border: 'none',
			width: w,
			height: h
		});
		return new $.modal(iframe);
	};
	
	$.fn.modalWeb = function(w,h) {
		this.click(function() {
			if (this.href != undefined) {
				$.modalWeb(this.href,w,h);
			}
			return false;
		});
	};
})(jQuery);
