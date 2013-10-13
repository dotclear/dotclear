dotclear.moduleExpander = function(line) {
        var td = line.firstChild;
        
        var img = document.createElement('img');
        img.src = dotclear.img_plus_src;
        img.alt = dotclear.img_plus_alt;
        img.className = 'expand';
        $(img).css('cursor','pointer');
        img.line = line;
        img.onclick = function() { dotclear.viewModuleContent(this,this.line); };
        
        td.insertBefore(img,td.firstChild);
};

dotclear.modulesExpander = function(line,lines) {
        var td = line.firstChild;

        var img = document.createElement('img');
        img.src = dotclear.img_plus_src;
        img.alt = dotclear.img_plus_alt;
        img.className = 'expand';
        $(img).css('cursor','pointer');
        img.lines = lines;
        img.onclick = function() { dotclear.viewModulesContent(this,this.lines); };

        td.insertBefore(img,td.firstChild);
};

dotclear.viewModulesContent = function(img,lines) {
        
        action = 'toggle';

        if (img.alt == dotclear.img_plus_alt) {
                img.src = dotclear.img_minus_src;
                img.alt = dotclear.img_minus_alt;
                action = 'open';
        } else {
                img.src = dotclear.img_plus_src;
                img.alt = dotclear.img_plus_alt;
                action = 'close';
        }
        
        lines.each(function() {
                var td = this.firstChild;
                dotclear.viewModuleContent(td.firstChild,td.firstChild.line,action);
        });
};

dotclear.viewModuleContent = function(img,line,action) {

        var action = action || 'toggle';
		var cols = $('td',$(line)).length
        var sp = line.id.split('_m_');
		var listId=sp[0];
		var moduleId= sp[1];

        var tr = document.getElementById('pe'+moduleId);
        
		if ( !tr && ( action == 'toggle' || action == 'open' ) ) {
                tr = document.createElement('tr');
                tr.id = 'pe'+moduleId;

                var td = document.createElement('td');
                td.colSpan = cols;
                td.className = 'expand';
                tr.appendChild(td);
                
                img.src = dotclear.img_minus_src;
                img.alt = dotclear.img_minus_alt;
                
                // Get post content
                $.get('services.php',{f:'getModuleById', id: moduleId, list: listId},function(data) {
                        var rsp = $(data).children('rsp')[0];
                        
                        if (rsp.attributes[0].value == 'ok') {
                                var author = $(rsp).find('author').text();
                                var details = $(rsp).find('details').text();
                                var support = $(rsp).find('support').text();
								var box = document.createElement('div');
                                var bloc = document.createElement('ul');
                                bloc.className = "mod-more";
                                
                                if (author) {
                                        $(bloc).append($('<li class="module-author">'+dotclear.msg.module_author+' '+author+'</li>'));
                                }
                                if (details) {
                                        var dd = '';
                                        dd += '<a class="module-details" href="'+details+'">'+dotclear.msg.module_details+'</a>';
                                        if (support) {
                                                dd += ' - ';
                                                dd += '<a class="module-support" href="'+support+'">'+dotclear.msg.module_support+'</a>';
                                        }
                                        $(bloc).append($('<li>'+dd+'</li>'));
                                }

                                $(td).append($(box).append(bloc));
                                
                                var section = $(rsp).find('section').text();
                                var tags = $(rsp).find('tags').text();
                                
				var boxb = document.createElement('div');
                                var blocb = document.createElement('ul');
                                blocb.className = "mod-more";
                                
                                if (section) {
                                        $(blocb).append($('<li class="module-section">'+dotclear.msg.module_section+' '+section+'</li>'));
                                }
                                if (tags) {
                                        $(blocb).append($('<li class="module-tags">'+dotclear.msg.module_tags+' '+tags+'</li>'));
                                }
                                $(td).append($(boxb).append(blocb));
                        } else {
                                alert($(rsp).find('message').text());
                        }
                });
                
                $(line).addClass('expand');
                line.parentNode.insertBefore(tr,line.nextSibling);
        }
        else if (tr && tr.style.display == 'none' && ( action == 'toggle' || action == 'open' ) )
        {
                $(tr).css('display', 'table-row');
                $(line).addClass('expand');
                img.src = dotclear.img_minus_src;
                img.alt = dotclear.img_minus_alt;
        }
        else if (tr && tr.style.display != 'none' && ( action == 'toggle' || action == 'close' ) )
        {
                $(tr).css('display', 'none');
                $(line).removeClass('expand');
                img.src = dotclear.img_plus_src;
                img.alt = dotclear.img_plus_alt;
        }
        
        parentTable = $(line).parents('table');
        if( parentTable.find('tr.expand').length == parentTable.find('tr.line').length ) {
                img = parentTable.find('tr:not(.line) th:first img');
                img.attr('src',dotclear.img_minus_src);
                img.attr('alt',dotclear.img_minus_alt);
        }
        
        if( parentTable.find('tr.expand').length == 0 ) {
                img = parentTable.find('tr:not(.line) th:first img');
                img.attr('src',dotclear.img_plus_src);
                img.attr('alt',dotclear.img_plus_alt);
        }
        
};


$(function() {
/*
	// expand all modules lines
	$('table.modules.expandable tr:not(.line)').each(function() {
		dotclear.modulesExpander(this,$('table.modules.expandable tr.line'));
	});
*/
	// expand a module line
	$('table.modules.expandable tr.line').each(function() {
		dotclear.moduleExpander(this);
	});

	// confirm module deletion
	$('td.module-actions form input[type=submit][name=delete]').click(function() {
		var module_id = $('input[name=module]',$(this).parent()).val();
		return window.confirm(dotclear.msg.confirm_delete_plugin.replace('%s',module_id));
	});
});