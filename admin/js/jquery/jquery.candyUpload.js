(function($) {
	// We need to create a global SWFUpload object to store instances
	window.SWFUpload = {
		instances: new Array(),
		movieCount: 0,
		version: "2.2.0 Beta 2",
		QUEUE_ERROR: {
			QUEUE_LIMIT_EXCEEDED:	-100,
			FILE_EXCEEDS_SIZE_LIMIT:	-110,
			ZERO_BYTE_FILE:		-120,
			INVALID_FILETYPE:		-130
		},
		UPLOAD_ERROR: {
			HTTP_ERROR:				-200,
			MISSING_UPLOAD_URL:			-210,
			IO_ERROR:					-220,
			SECURITY_ERROR:			-230,
			UPLOAD_LIMIT_EXCEEDED:		-240,
			UPLOAD_FAILED:				-250,
			SPECIFIED_FILE_ID_NOT_FOUND:	-260,
			FILE_VALIDATION_FAILED:		-270,
			FILE_CANCELLED:			-280,
			UPLOAD_STOPPED:			-290
		},
		FILE_STATUS: {
			QUEUED:		-1,
			IN_PROGRESS:	-2,
			ERROR:		-3,
			COMPLETE:		-4,
			CANCELLED:	-5
		},
		BUTTON_ACTION: {
			SELECT_FILE:	-100,
			SELECT_FILES:	-110,
			START_UPLOAD:	-120
		},
		CURSOR: {
			ARROW:	-1,
			HAND:	-2
		},
		WINDOW_MODE: {
			WINDOW:		'window',
			TRANSPARENT:	'transparent',
			OPAQUE:		'opaque'
		}
	};
	
	$.uploader = function(settings,callbacks) {
		return new $._uploader(settings,callbacks);
	};
	
	$._uploader = function(settings,callbacks) {
		var defaults = {
			debug: false,
			upload_url: '',
			flash_movie: '',
			movie_name: null,
			params: null,
			use_query_string: null,
			requeue_on_error: false,
			file_types: '*.*',
			file_types_description: 'All files',
			file_size_limit: 0,
			file_upload_limit: 0,
			file_queue_limit: -1,
			target_element: null
		};
		this.params = $.extend(defaults,settings);
		
		var default_callbacks = {
			flashReady: function() {},
			fileDialogComplete: function() {},
			fileDialogStart: function() {},
			fileQueued: function() {},
			fileQueueError: function() {},
			uploadStart: function() {},
			uploadProgress: function() {},
			uploadError: function() {},
			uploadSuccess: function() {},
			uploadComplete: function() {},
			debug: function() {}
		};
		this.callbacks = $.extend(default_callbacks,callbacks);
		
		return this.build();
	};
	
	$._uploader.prototype = {
		log: function(msg) {
			if (!this.params.debug) { return; }
			try {
				console.log(msg);
			} catch(e) {}
		},
		
		/* Constructor */
		build: function(settings) {
			if (!this.params.upload_url || !this.params.movie_name) {
				throw('Configuration error. Please make sure you specified movie_name and upload_url options.');
			}
			
			if ($.browser.opera) {
				return this;
			}
			
			// Get a real bytes size limit (if we want to reuse it somewhere)
			this.params.file_size_limit = this.getFileSizeLimit(this.params.file_size_limit);
			
			// Flash vars
			var fv = new Array();
			this.addFlashVar(fv,'debugEnabled',this.params.debug);
			this.addFlashVar(fv,'movieName',this.params.movie_name);
			this.addFlashVar(fv,'uploadURL',this.params.upload_url);
			this.addFlashVar(fv,'fileTypes',this.params.file_types);
			this.addFlashVar(fv,'fileTypesDescription',this.params.file_types_description);
			this.addFlashVar(fv,'params',this.params.params);
			this.addFlashVar(fv,'fileSizeLimit',this.params.file_size_limit + 'b');
			this.addFlashVar(fv,'fileUploadLimit',this.params.file_upload_limit);
			this.addFlashVar(fv,'fileQueueLimit',this.params.file_queue_limit);
			this.addFlashVar(fv,'requeueOnError',this.params.requeue_on_error);
			this.addFlashVar(fv,'buttonImageURL','');
			this.addFlashVar(fv,'buttonWidth',500);
			this.addFlashVar(fv,'buttonHeight',500);
			this.addFlashVar(fv,'buttonText','');
			this.addFlashVar(fv,'buttonTextTopPadding',0);
			this.addFlashVar(fv,'buttonTextLeftPadding',0);
			this.addFlashVar(fv,'buttonTextStyle','');
			this.addFlashVar(fv,'buttonAction',this.params.upload_limit == 1 ? -100 : -110);
			this.addFlashVar(fv,'buttonDisabled',false);
			this.addFlashVar(fv,'buttonCursor',-2);
			this.addFlashVar(fv,'button_window_mode','window');
			
			// Create SWFUpload Instance
			this.flashBind();
			
			// Flash object
			var flash =
			'<object id="' + this.params.movie_name + '" data="' + this.params.flash_movie + '" ' +
			'type="application/x-shockwave-flash" width="10" height="10">' +
			'<param name="movie" value="' + this.params.flash_movie + '" />'+
			'<param name="wmode" value="transparent" />'+
			'<param name="menu" value="false" />'+
			'<param name="allowScriptAccess" value="always" />'+
			'<param name="flashvars" value="' + fv.join('&amp;') + '" />'+
			'</object>';
			
			// Flash container
			this.container = $('<span></span>');
			if (this.params.target_element == null) {
				this.container.css({
					display: 'block',
					position: 'absolute',
					left: $(document).scrollLeft()+'px',
					top: $(document).scrollTop()+'px',
					width: '30px',
					height: '30px',
					background: '#f00'
				});
				
				$('body').append(this.container);
			} else {
				$(this.params.target_element).append(this.container);
				this.container.css({
					display: 'block',
					position: 'absolute',
					top: 0,
					left: 0,
					zIndex: 1
				});
			}
			this.container[0].innerHTML = flash;
			this.movie = document.getElementById(this.params.movie_name);
			
			return this;
		},
		
		addFlashVar: function(fv,n,v) {
			if (v != null) {
				fv.push(n + '=' + encodeURIComponent(v));
			}
		},
		
		// Bind flash events to callbacks.events
		flashBind: function() {
			_this = this;
			var events = {
				flashReady: function() {
					if (window[_this.params.movie_name] == undefined) {
						window[_this.params.movie_name] = _this.movie;
					}
					_this.flashBindEvent('flashReady',arguments);
				},
				fileDialogComplete: function() { _this.flashBindEvent('fileDialogComplete',arguments); },
				fileDialogStart: function() { _this.flashBindEvent('fileDialogStart',arguments); },
				fileQueued: function(file_object) { _this.flashBindEvent('fileQueued',arguments); },
				fileQueueError: function(file_object,error_code,error_msg) { _this.flashBindEvent('fileQueueError',arguments); },
				uploadStart: function() { _this.flashBindEvent('uploadStart',arguments); },
				uploadProgress: function() { _this.flashBindEvent('uploadProgress',arguments); },
				uploadError: function() { _this.flashBindEvent('uploadError',arguments); },
				uploadSuccess: function() { _this.flashBindEvent('uploadSuccess',arguments); },
				uploadComplete: function() { _this.flashBindEvent('uploadComplete',arguments); },
				debug: function() { _this.flashBindEvent('debug',arguments); }
			};
			window.SWFUpload.instances[this.params.movie_name] = events;
			window.SWFUpload.movieCount++;
		},
		
		// Each flash event is called as callbacks.events([arg],...) with this = uploader object
		flashEventQueue: [],
		flashBindEvent: function(evt,a) {
			a = a || new Array();
			
			var _this = this;
			if ($.isFunction(this.callbacks[evt])) {
				// Queue the event
				this.flashEventQueue.push(function () {
						this.callbacks[evt].apply(this,a);
				});
				
				// Execute the next queued event
				setTimeout(function () {
					_this.flashExecuteNextEvent();
				},0);
			} else if (this.callbacks[evt] !== null) {
				throw 'Event handler ' + evt + ' is unknown or is not a function';
			}

		},
		flashExecuteNextEvent: function() {
			var f = this.flashEventQueue ? this.flashEventQueue.shift() : null;
			if ($.isFunction(f)) {
				f.apply(this);
			}
		},
		
		destroy: function() {
			try {
				this.StopUpload();
				$(this.movie).remove();
				SWFUpload.instances[this.params.movie_name] = null;
				SWFUpload.movieCount--;
				delete SWFUpload.instances[this.movieName];
				delete window[this.movieName];
				
				return true;
			} catch(e) {
				return false;
			}
		},
		
		// Flash uploader functions
		StartUpload: function(file_id) {
			return this.movie.StartUpload(file_id);
		},
		ReturnUploadStart: function(value) {
			return this.movie.ReturnUploadStart(value);
		},
		StopUpload: function() {
			return this.movie.StopUpload();
		},
		CancelUpload: function(file_id) {
			return this.movie.CancelUpload(file_id);
		},
		GetStats: function() {
			return this.movie.GetStats();
		},
		SetStats: function(stats) {
			return this.movie.SetStats(stats);
		},
		GetFile: function(file_id) {
			return this.movie.GetFile(file_id);
		},
		GetFileByIndex: function(file_index) {
			return this.movie.GetFileByIndex(file_index);
		},
		
		// Size formater, that's better
		getFileSizeLimit: function(size) {
			var value = 0;
			var unit = 'kb';
			
			size = $.trim(size.toLowerCase());
			
			var values = size.match(/^\d+/);
			if (values != null && values.length > 0) {
				value = parseInt(values[0]);
			}
			
			var units = size.match(/(b|kb|mb|gb)/);
			if (units != null && units.length > 0) {
				unit = units[0];
			}
			
			var multiplier = 1024;
			if (unit === "b") {
				multiplier = 1;
			} else if (unit === "mb") {
				multiplier = 1048576;
			} else if (unit === "gb") {
				multiplier = 1073741824;
			}
			
			return value * multiplier;
		}
	};
})(jQuery);

(function($) {
	$.fn.candyUpload = function(settings,callbacks) {
		new $._candyUpload(this,settings,callbacks);
		return this;
	};
	
	$._candyUpload = function(target,settings,callbacks) {
		var defaults = {
			debug: false,
			upload_url: '',
			params: null,
			flash_movie: '',
			file_types: '*.*',
			file_types_description: 'All files',
			file_size_limit: 0,
			file_upload_limit: 0,
			file_queue_limit: -1,
			
			callbacks: {} // Put here all callbacks you want, named as in $.uploader events
		};
		this.params = $.extend(defaults,settings);
		this.params.movie_name = 'SWFU-' + (window.SWFUpload.instances.length + 1);
		this.target = target;
		
		// Create controls
		this.createControls();
		
		this.target.hide().after(this.ctrl.block).hide();
		this.params.target_element = this.ctrl.btn_browse.parent().get(0);
		
		// Uploader init
		var _this = this;
		this.upldr = $.uploader(this.params,{
			debug: function(msg) {
				$('body').append('<pre class="debug">' + msg + '</pre>');
				_this.bindEvent('debug',arguments);
			},
			flashReady: function() {
				_this.initControls(this);
				_this.bindEvent('flashReady',arguments);
				
				this.movie.style.width = _this.ctrl.btn_browse.width()+'px';
				this.movie.style.height = _this.ctrl.btn_browse.height()+'px';
			},
			fileDialogComplete: function(num_ref_files,num_queue_files) {
				_this.bindEvent('fileDialogComplete',arguments);
			},
			fileDialogStart: function() {
				_this.bindEvent('fileQueued',arguments);
			},
			fileQueued: function(o) {
				_this.appendFile(this,o);
				_this.refreshControls(this);
				_this.bindEvent('fileQueued',arguments);
			},
			fileQueueError: function(o,code,msg) {
				var codes = window.SWFUpload.QUEUE_ERROR;
				switch (code) {
					case codes.QUEUE_LIMIT_EXCEEDED:
						_this.queueErrorMsg(_this.locales.limit_exceeded);
						break;
					case codes.FILE_EXCEEDS_SIZE_LIMIT:
						_this.queueErrorMsg(_this.locales.size_limit_exceeded);
						break;
					case codes.ZERO_BYTE_FILE:
					case codes.INVALID_FILETYPE:
						_this.queueErrorMsg(msg);
						break;
				}
				_this.bindEvent('fileQueueError',arguments);
			},
			uploadStart: function() {
				this.ReturnUploadStart(true);
				_this.bindEvent('uploadStart',arguments);
			},
			uploadProgress: function(o,bytes,total) {
				_this.fileProgressBar(o.id,bytes,total);
				_this.bindEvent('uploadProgress',arguments);
			},
			uploadError: function(o,code,msg) {
				var codes = window.SWFUpload.UPLOAD_ERROR;
				switch (code) {
					case codes.FILE_CANCELLED:
						_this.fileErrorMsg(o.id,_this.locales.canceled);
						break;
					case codes.HTTP_ERROR:
						_this.fileErrorMsg(o.id,_this.locales.http_error + ' ' + msg);
						break;
					case codes.MISSING_UPLOAD_URL:
					case codes.IO_ERROR:
					case codes.SECURITY_ERROR:
					case codes.UPLOAD_LIMIT_EXCEEDED:
					case codes.UPLOAD_FAILED:
					case codes.SPECIFIED_FILE_ID_NOT_FOUND:
					case codes.FILE_VALIDATION_FAILED:
					case codes.FILE_CANCELLED:
					case codes.UPLOAD_STOPPED:
						_this.fileErrorMsg(o.id,_this.locales.error + ' ' + msg);
						break;
				}
				_this.refreshControls(this);
				_this.removeFileCancel(o);
				_this.bindEvent('uploadError',arguments);
			},
			uploadSuccess: function(o,data) {
				_this.fileProgressBar(o.id,1,1);
				_this.refreshControls(this);
				_this.removeFileCancel(o);
				_this.bindEvent('uploadSuccess',arguments);
			},
			uploadComplete: function(o) {
				// Once completed, start next queued upload
				this.StartUpload();
				_this.refreshControls(this);
				_this.bindEvent('uploadComplete',arguments);
			}
		});
	};
	
	$._candyUpload.prototype = {
		locales: {
			max_file_size: 'Maximum file size allowed:',
			limit_exceeded: 'Limit exceeded.',
			size_limit_exceeded: 'File size exceeds allowed limit.',
			canceled: 'Canceled.',
			http_error: 'HTTP Error:',
			error: 'Error:',
			choose_file: 'Choose file',
			choose_files: 'Choose files',
			cancel: 'Cancel',
			clean: 'Clean',
			upload: 'Upload',
			no_file_in_queue: 'No file in queue.',
			file_in_queue: '1 file in queue.',
			files_in_queue: '%d files in queue.',
			queue_error: 'Queue error:'
		},
		ctrl: {
			block: $('<div class="cu-ctrl"></div>'),
			files: null
		},
		
		createControls: function() {
			this.ctrl.btn_browse = $('<a href="#">&nbsp;</a>').click(function() {
				return false;
			});
			
			this.ctrl.btn_cancel = $('<a href="#">' + this.locales.cancel + '</a>').click(function() {
				return false;
			});
			
			this.ctrl.btn_clean = $('<a href="#">' + this.locales.clean + '</a>').click(function() {
				return false;
			});
			
			this.ctrl.btn_upload = $('<a href="#">' + this.locales.upload + '</a>').click(function() {
				return false;
			});
			
			this.ctrl.msg = $('<div class="cu-msg">' + this.locales.no_file_in_queue + '</div>').appendTo(this.ctrl.block);
			
			var btn = $('<div class="cu-btn"></div>').appendTo(this.ctrl.block);
			var brw = $('<span class="cu-btn-browse"></span>').append(this.ctrl.btn_browse).appendTo(btn);
			$('<span class="cu-btn-upload"></span>').append(this.ctrl.btn_upload).appendTo(btn).hide();
			$('<span class="cu-btn-cancel"></span>').append(this.ctrl.btn_cancel).appendTo(btn).hide();
			$('<span class="cu-btn-clean"></span>').append(this.ctrl.btn_clean).appendTo(btn).hide();
			
			this.bindEvent('createControls');
		},
		
		initControls: function(upldr) {
			if (this.params.file_queue_limit == 1) {
				this.ctrl.btn_browse.text(this.locales.choose_file);
			} else {
				this.ctrl.btn_browse.text(this.locales.choose_files);
			}
			
			var _this = this;
			
			this.ctrl.btn_cancel.click(function() {
				_this.cancelQueue(upldr);
				return false;
			});
			
			this.ctrl.btn_clean.click(function() {
				_this.cleanQueue(upldr);
				return false;
			});
			
			this.ctrl.btn_upload.click(function() {
				_this.uploadQueue(upldr);
				return false;
			});
			
			var size = this.formatSize(upldr.params.file_size_limit);
			$('<div class="cu-maxsize">' + this.locales.max_file_size + ' ' + size + '</div>').appendTo(this.ctrl.block);
		},
		
		refreshControls: function(upldr) {
			if (!this.ctrl.files || this.ctrl.files.length == 0) {
				return;
			}
			
			var stats = upldr.GetStats();
			
			if (stats.files_queued > 0) {
				this.ctrl.btn_cancel.parent().show();
				this.ctrl.btn_upload.parent().show();
				if (this.params.file_queue_limit > 0 && this.params.file_queue_limit == stats.files_queued) {
					this.ctrl.btn_browse.hide();
				} else {
					this.ctrl.btn_browse.show();
				}
				if (stats.files_queued > 1) {
					var msg = this.locales.files_in_queue.replace(/%d/,stats.files_queued);
				} else {
					var msg = this.locales.file_in_queue;
				}
			} else {
				this.ctrl.btn_browse.show();
				this.ctrl.btn_cancel.parent().hide();
				this.ctrl.btn_upload.parent().hide();
				var msg = this.locales.no_file_in_queue;
			}
			
			this.ctrl.msg.removeClass('cu-error').text(msg);
			
			if (stats.successful_uploads > 0 || stats.upload_errors > 0 || stats.upload_cancelled > 0) {
				this.ctrl.btn_clean.parent().show();
			} else {
				this.ctrl.btn_clean.parent().hide();
			}
		},
		
		removeFileCancel: function(o) {
			$('#' + o.id + ' span.cu-filecancel',this.ctrl.files).remove();
		},
		
		appendFile: function(upldr,o) {
			if (!this.ctrl.files) {
				this.ctrl.files = $('<div class="cu-files"></div>');
				this.ctrl.msg.after(this.ctrl.files);
			}
			
			var fileblock = $('<div class="cu-file" id="' + o.id + '">' +
					'<div class="cu-fileinfo"><span class="cu-filename">' + o.name + '</span> ' + 
					'<span class="cu-filesize">(' + this.formatSize(o.size) + ')</span> ' +
					'<span class="cu-filecancel"><a href="#">cancel</a></span> ' +
					'<span class="cu-filemsg"></span>' +
					'</div>');
			
			$('span.cu-filecancel a',fileblock).click(function() {
				upldr.CancelUpload(o.id);
				return false;
			});
			this.ctrl.files.append(fileblock);
		},
		
		fileProgressBar: function(file_id,bytes,total) {
			var bar = $('#' + file_id + ' div.cu-progress>div',this.ctrl.files);
			if (bar.length == 0) {
				$('#' + file_id,this.ctrl.files).append('<div class="cu-progress"><div>&nbsp;</div></div>');
				bar = $('#' + file_id + ' div.cu-progress>div',this.ctrl.files);
			}
			
			var percent = Math.round((bytes * 100) / total);
			bar.css('width',percent+'%').text(percent + '%');
		},
		
		fileMsg: function(file_id,msg,error) {
			error = error || false;
			var span = $('#' + file_id + ' span.cu-filemsg',this.ctrl.files).attr('class','cu-filemsg');
			if (error) {
				span.addClass('cu-error');
			}
			span.text(msg);
		},
		
		fileErrorMsg: function(file_id,msg) {
			this.fileMsg(file_id,msg,true);
		},
		
		cancelQueue: function(upldr) {
			if (!this.ctrl.files || this.ctrl.files.length == 0) {
				return;
			}
			
			this.ctrl.files.children('div').each(function() {
				upldr.CancelUpload(this.id);
			});
		},
		
		uploadQueue: function(upldr) {
			if (!this.ctrl.files || this.ctrl.files.length == 0) {
				return;
			}
			
			upldr.StartUpload();
		},
		
		cleanQueue: function(upldr) {
			var _this = this;
			var e = $('div.cu-file',this.ctrl.files).not(':has(span.cu-filecancel a)');
			
			e.filter(':last').slideUp(200,function() {
				$(this).remove();
				if (e.length == 1) {
					upldr.SetStats({successful_uploads:0, upload_errors:0, upload_cancelled:0});
					_this.refreshControls(upldr);
				} else if (e.length > 1) {
					_this.cleanQueue(upldr);
				}
			});
		},
		
		queueErrorMsg: function(msg) {
			this.ctrl.msg.addClass('cu-error').text(this.locales.queue_error + ' ' + msg);
		},
		
		formatSize: function(s) {
			var a_size = Array('B', 'KB', 'MB', 'GB', 'TB');
			var i_index = 0;
			while (s > 1024) {
				i_index++;
				s/=1024;
			}
			return (Math.round(s * 100) /100) + ' ' + a_size[i_index];
		},
		
		bindEvent: function(evt,a) {
			if (this.params.callbacks[evt] != undefined && $.isFunction(this.params.callbacks[evt])) {
				a = a || new Array();
				this.params.callbacks[evt].apply(this,a);
			}
		}
	};
})(jQuery);