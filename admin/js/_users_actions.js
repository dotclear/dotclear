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
			prop = e.name.match(re);
			if (!prop) {
				continue;
			}
			if (perms[prop[1]] == undefined) {
				perms[prop[1]] = {};
			}
			perms[prop[1]][prop[2]] = e;
		}
		
		// Update elements status
		var E;
		for (blog in perms) {
			for (perm in perms[blog]) {
				E = perms[blog][perm];
				E.onclick = function() {};
				
				if (perm == 'admin' && !E.disabled) {
					perms[blog]['usage'].disabled = E.checked;
					perms[blog]['publish'].disabled = E.checked;
					perms[blog]['delete'].disabled = E.checked;
					perms[blog]['contentadmin'].disabled = E.checked;
					perms[blog]['categories'].disabled = E.checked;
					perms[blog]['media'].disabled = E.checked;
					perms[blog]['media_admin'].disabled = E.checked;
					E.onclick = function() { $(this.form).updatePermissionsForm(); };
				} else if (perm == 'contentadmin' && !E.disabled) {
					perms[blog]['usage'].disabled = E.checked;
					perms[blog]['publish'].disabled = E.checked;
					perms[blog]['delete'].disabled = E.checked;
					E.onclick = function() { $(this.form).updatePermissionsForm(); };
				} else if (perm == 'media_admin' && !E.disabled) {
					perms[blog]['media'].disabled = E.checked;
					E.onclick = function() { $(this.form).updatePermissionsForm(); };
				}
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