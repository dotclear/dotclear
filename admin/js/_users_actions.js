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
				$(e).click(function(){
					P = this.name.match(re);
					
					perms[P[1]]['usage'].checked = this.checked;
					perms[P[1]]['publish'].checked = this.checked;
					perms[P[1]]['delete'].checked = this.checked;
					perms[P[1]]['contentadmin'].checked = this.checked;
					perms[P[1]]['categories'].checked = this.checked;
					perms[P[1]]['media'].checked = this.checked;
					perms[P[1]]['media_admin'].checked = this.checked;
					perms[P[1]]['usage'].disabled = this.checked;
					perms[P[1]]['publish'].disabled = this.checked;
					perms[P[1]]['delete'].disabled = this.checked;
					perms[P[1]]['contentadmin'].disabled = this.checked;
					perms[P[1]]['categories'].disabled = this.checked;
					perms[P[1]]['media'].disabled = this.checked;
					perms[P[1]]['media_admin'].disabled = this.checked;
					
				});
			// select related permissions for content admin
			} else if (prop[2] == 'contentadmin') {
				$(e).click(function(){
					P = this.name.match(re);
					
					perms[P[1]]['usage'].checked = this.checked;
					perms[P[1]]['publish'].checked = this.checked;
					perms[P[1]]['delete'].checked = this.checked;
					perms[P[1]]['usage'].disabled = this.checked;
					perms[P[1]]['publish'].disabled = this.checked;
					perms[P[1]]['delete'].disabled = this.checked;
					
				});
			// select related permissions for media admin
			} else if (prop[2] == 'media_admin') {
				$(e).click(function(){
					P = this.name.match(re);
					
					perms[P[1]]['media'].checked = this.checked;
					perms[P[1]]['media'].disabled = this.checked;
					
				});
			}
		}
	});
};

$(function() {
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});
	$('#permissions-form').updatePermissionsForm();
});