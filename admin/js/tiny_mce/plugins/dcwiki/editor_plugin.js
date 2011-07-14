(function() {
	tinymce.create('tinymce.plugins.dcwiki',{
		// Init.
		init: function(ed, url) {
			var t = this;
			
			t.editor = ed;
			
			ed.onBeforeSetContent.add(function(ed,o) {
				o.content = t._wiki2html(o.content);
			});
			ed.onPostProcess.add(function(ed, o) {
				if (o.set)
					o.content = t._wiki2html(o.content);
				if (o.get)
					o.content = t._html2wiki(o.content);
			});
			
			// Register commands
			ed.addCommand('Bold', function() {
				var s = ed.selection.getContent();
				var res = '__' + s + '__';
				
				ed.execCommand('mceInsertContent',false,res);
			});
			ed.addCommand('Italic', function() {
				var s = ed.selection.getContent();
				var res = "''" + s + "''";
				
				ed.execCommand('mceInsertContent',false,res);
			});
			ed.addCommand('Underline', function() {
				var s = ed.selection.getContent();
				var res = '++' + s + '++';
				
				ed.execCommand('mceInsertContent',false,res);
			});
			ed.addCommand('Strikethrough', function() {
				var s = ed.selection.getContent();
				var res = '--' + s + '--';
				
				ed.execCommand('mceInsertContent',false,res);
			});
			ed.addCommand('mceBlockQuote', function() {
				var s = ed.selection.getContent();
				var res = '<br />> ' + s;
				
				ed.execCommand('mceInsertContent',false,res);
			});
			ed.addCommand('InsertUnorderedList', function() {
				var s = ed.selection.getContent();
				var res = '*' + s;
				
				ed.execCommand('mceInsertContent',false,res);
			});
			ed.addCommand('InsertOrderedList', function() {
				var s = ed.selection.getContent();
				var res = '#' + s;
				
				ed.execCommand('mceInsertContent',false,res);
			});
			
			ed.addCommand('dcCode', function() {
				var s = ed.selection.getContent();
				var res = '@@' + s + '@@';
				
				ed.execCommand('mceInsertContent',false,res);
			});
			ed.addCommand('dcQuote', function() {
				var s = ed.selection.getContent();
				var res = '{{' + s + '}}';
				
				ed.execCommand('mceInsertContent',false,res);
			});
		},
		
		getInfo: function() {
			return {
				longname: 'Dotclear wiki controls',
				author: 'Tomtom for dotclear',
				authorurl: 'http://dotclear.org',
				infourl: '',
				version: tinymce.majorVersion + "." + tinymce.minorVersion
			};
		},
		
		// HTML -> Dotclear wiki
		_html2wiki : function(s) {
			s = tinymce.trim(s);
			
			function rep(re, str) {
				s = s.replace(re, str);
			};
			
			rep(/<br(\s*\/)?>/gi,"\n");
			rep(/&lt;/gi,'<');
			rep(/&gt;/gi,'>');
			
			return s;
		},
		
		// Dotclear wiki -> HTML
		_wiki2html : function(s) {
			s = tinymce.trim(s);
			
			function rep(re, str) {
				s = s.replace(re, str);
			};
			
			rep(/</gi,'&lt;');
			rep(/>/gi,'&gt;');
			rep(/\n/g,'<br />');
			
			return s;
		}
	});
	
	// Register plugin
	tinymce.PluginManager.add('dcwiki',tinymce.plugins.dcwiki);
})();