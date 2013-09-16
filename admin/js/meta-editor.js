//~ metaEditor & metaEditor.prototype should go to the core.
function metaEditor(target,meta_field,meta_type) {
	this.target = target;
	this.meta_field = meta_field;
	this.meta_type = meta_type;
};

metaEditor.prototype = {
	meta_url: '',
	text_confirm_remove: 'Are you sure you want to remove this %s?',
	text_add_meta: 'Add a %s to this entry',
	text_choose: 'Choose from list',
	text_all: 'all',
	text_separation: 'Separate each %s by comas',
	
	target: null,
	meta_type: null,
	meta_dialog: null,
	meta_field: null,
	submit_button: null,
	post_id: false,
	
	service_uri: 'services.php',
	
	displayMeta: function(type,post_id) {
		this.meta_type = type;
		this.post_id = post_id;
		this.target.empty();
		
		this.meta_dialog = $('<input type="text" class="ib" />');
		this.meta_dialog.attr('title',this.text_add_meta.replace(/%s/,this.meta_type));
		this.meta_dialog.attr('id','post_meta_input');
		// Meta dialog input
		this.meta_dialog.keypress(function(evt) { // We don't want to submit form!
			if (evt.keyCode == 13) {
				This.addMeta(this.value);
				return false;
			}
			return true;
		});
		
		var This = this;
		
		this.submit_button = $('<input type="button" value="ok" class="ib" />');
		this.submit_button.click(function() {
			var v = This.meta_dialog.val();
			This.addMeta(v);
			return false;
		});
		
		this.addMetaDialog();
		
		if (this.post_id == false) {
			this.target.append(this.meta_field);
		}
		this.displayMetaList();
	},
	
	displayMetaList: function() {
		var li;
		if (this.meta_list == undefined) {
			this.meta_list = $('<ul class="metaList"></ul>');
			this.target.prepend(this.meta_list);
		}
		
		if (this.post_id == false) {
			var meta = this.splitMetaValues(this.meta_field.val());
			
			this.meta_list.empty();
			for (var i=0; i<meta.length; i++) {
				li = $('<li>'+meta[i]+'</li>');
				a_remove = $('<a class="metaRemove" href="#">[x]</a>');
				a_remove.get(0).caller = this;
				a_remove.get(0).meta_id = meta[i];
				a_remove.click(function() {
					this.caller.removeMeta(this.meta_id);
					return false;
				});
				li.append('&nbsp;').append(a_remove);
				this.meta_list.append(li);
			}
		} else {
			var This = this;
			var params = {
				f: 'getMeta',
				metaType: this.meta_type,
				sortby: 'metaId,asc',
				postId: this.post_id
			};
			
			$.get(this.service_uri,params,function(data) {
				data = $(data);
				
				if (data.find('rsp').attr('status') != 'ok') { return; }
				
				This.meta_list.empty();
				data.find('meta').each(function() {
					var meta_id = $(this).text();
					li = $('<li><a href="' + This.meta_url + $(this).attr('uri') + '">'+meta_id+'</a></li>');
					a_remove = $('<a class="metaRemove" href="#">[x]</a>');
					a_remove.get(0).caller = This;
					a_remove.get(0).meta_id = meta_id;
					a_remove.click(function() {
						this.caller.removeMeta(this.meta_id);
						return false;
					});
					li.append('&nbsp;').append(a_remove);
					This.meta_list.append(li);
				});
			});
		}
	},
	
	addMetaDialog: function() {
		
		if (this.submit_button == null) {
			this.target.append($('<p></p>').append(this.meta_dialog));
		} else {
			this.target.append($('<p></p>').append(this.meta_dialog).append(' ').append(this.submit_button));
		}
		
		if (this.text_separation != '') {
			this.target.append($('<p></p>').addClass('form-note').append(this.text_separation.replace(/%s/,this.meta_type)));
		}
		
		this.showMetaList(metaEditor.prototype.meta_type,this.target);
		
	},
	
	showMetaList: function(type,target) {
		
		var params = {
			f: 'getMeta',
			metaType: this.meta_type,
			sortby: 'metaId,asc'
		};
		
		if (type == 'more') {
			params.limit = '30';
		}
		
		var This = this;
		
		$.get(this.service_uri,params,function(data) {
			
			var pl = $('<p class="addMeta"></p>');
			
			$('.addMeta').remove();
			
			if ($(data).find('meta').length > 0) {
				pl.empty();
				var meta_link;
				
				$(data).find('meta').each(function(i) {
					meta_link = $('<a href="#">' + $(this).text() + '</a>');
					meta_link.get(0).meta_id = $(this).text();
					meta_link.click(function() {
						var v = This.splitMetaValues(This.meta_dialog.val() + ',' + this.meta_id);
						This.meta_dialog.val(v.join(','));
						return false;
					});
					
					if (i>0) {
						pl.append(', ');
					}
					pl.append(meta_link);
				});
				
				if (type == 'more') {
					var a_more = $('<a href="#" class="metaGetMore"></a>');
					a_more.append(This.text_all + String.fromCharCode(160)+String.fromCharCode(187));
					a_more.click(function() {
						This.showMetaList('all',target);
						return false;
					});
					pl.append(', ').append(a_more);
					
					pl.addClass('hide');
					
					var pa = $('<p></p>');
					target.append(pa);
					
					var a = $('<a href="#" class="metaGetList">' + This.text_choose + '</a>');
					a.click(function() {
						$('.addMeta').removeClass('hide');
						$('.metaGetList').remove();
						return false;
					});
					
					pa.append(a);
				}
				
				target.append(pl);
				
			} else {
				pl.empty();
			}
		});
	},
	
	addMeta: function(str) {
		str = this.splitMetaValues(str).join(',');
		if (this.post_id == false) {
			str = this.splitMetaValues(this.meta_field.val() + ',' + str);
			this.meta_field.val(str);
			
			this.meta_dialog.val('');
			this.displayMetaList();
		} else {
			var params = {
				xd_check: dotclear.nonce,
				f: 'setPostMeta',
				postId: this.post_id,
				metaType: this.meta_type,
				meta: str
			};
			
			var This = this;
			$.post(this.service_uri,params,function(data) {
				if ($(data).find('rsp').attr('status') == 'ok') {
					This.meta_dialog.val('');
					This.displayMetaList();
				} else {
					alert($(data).find('message').text());
				}
			});
		}
	},
	
	removeMeta: function(meta_id) {
		if (this.post_id == false) {
			var meta = this.splitMetaValues(this.meta_field.val());
			for (var i=0; i<meta.length; i++) {
				if (meta[i] == meta_id) {
					meta.splice(i,1);
					break;
				}
			}
			this.meta_field.val(meta.join(','));
			this.displayMetaList();
		} else {
			var text_confirm_msg = this.text_confirm_remove.replace(/%s/,this.meta_type);
			
			if (window.confirm(text_confirm_msg)) {
				var This = this;
				var params = {
					xd_check: dotclear.nonce,
					f: 'delMeta',
					postId: this.post_id,
					metaId: meta_id,
					metaType: this.meta_type
				};
				
				$.post(this.service_uri,params,function(data) {
					if ($(data).find('rsp').attr('status') == 'ok') {
						This.displayMetaList();
					} else {
						alert($(data).find('message').text());
					}
				});
			}
		}
	},
	
	splitMetaValues: function(str) {
		function inArray(needle,stack) {
			for (var i=0; i<stack.length; i++) {
				if (stack[i] == needle) {
					return true;
				}
			}
			return false;
		}
		
		var res = new Array();
		var v = str.split(',');
		v.sort();
		for (var i=0; i<v.length; i++) {
			v[i] = v[i].replace(/^\s*/,'').replace(/\s*$/,'');
			if (v[i] != '' && !inArray(v[i],res)) {
				res.push(v[i]);
			}
		}
		res.sort();
		return res;
	}
};