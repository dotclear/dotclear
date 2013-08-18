jQuery.fn.updatePermissionsForm = function() {
	return this.each(function() {
		
		var perms = {};
		var re = /^perm\[(.+?)\]\[(.+?)\]$/;
		var e,prop;
		
		// Building a nice object of form elements
		for (var i=0; i<this.elements.length; i++) {
			e = this.elements[i];
			
			if (e.name == undefined) {
				continue;
			}
			var prop;
			prop = e.name.match(re);
			if (!prop) {
				continue;
			}
			if (perms[prop[1]] == undefined) {
				perms[prop[1]] = {};
			}
			perms[prop[1]][prop[2]] = e;
			
			// select related permissions for admin
			if (prop[2] == 'admin') {
				if (e.checked) {
					admin(e,perms,re);
				}
				$(e).click(function(){
					admin(this,perms,re);
				});
			// select related permissions for content admin
			} else if (prop[2] == 'contentadmin') {
				if (e.checked) {
					contentadmin(e,perms,re);
				}
				$(e).click(function(){
					contentadmin(this,perms,re);
				});
			// select related permissions for media admin
			} else if (prop[2] == 'media_admin') {
				if (e.checked) {
					mediaadmin(e,perms,re);
				}
				$(e).click(function(){
					mediaadmin(this,perms,re);
				});
			}
		}
		
		function admin(E,perms,re) {
					P = E.name.match(re);
					
					perms[P[1]]['usage'].checked = E.checked;
					perms[P[1]]['publish'].checked = E.checked;
					perms[P[1]]['delete'].checked = E.checked;
					perms[P[1]]['contentadmin'].checked = E.checked;
					perms[P[1]]['categories'].checked = E.checked;
					perms[P[1]]['media'].checked = E.checked;
					perms[P[1]]['media_admin'].checked = E.checked;
					perms[P[1]]['usage'].disabled = E.checked;
					perms[P[1]]['publish'].disabled = E.checked;
					perms[P[1]]['delete'].disabled = E.checked;
					perms[P[1]]['contentadmin'].disabled = E.checked;
					perms[P[1]]['categories'].disabled = E.checked;
					perms[P[1]]['media'].disabled = E.checked;
					perms[P[1]]['media_admin'].disabled = E.checked;
		}
		
		function contentadmin(E,perms,re) {
					P = E.name.match(re);
					
					perms[P[1]]['usage'].checked = E.checked;
					perms[P[1]]['publish'].checked = E.checked;
					perms[P[1]]['delete'].checked = E.checked;
					perms[P[1]]['usage'].disabled = E.checked;
					perms[P[1]]['publish'].disabled = E.checked;
					perms[P[1]]['delete'].disabled = E.checked;
		}
		
		function mediaadmin(E,perms,re) {
					P = E.name.match(re);
					
					perms[P[1]]['media'].checked = E.checked;
					perms[P[1]]['media'].disabled = E.checked;
		}
		
		
	});
};

$(function() {
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});
	$('#permissions-form').updatePermissionsForm();
});