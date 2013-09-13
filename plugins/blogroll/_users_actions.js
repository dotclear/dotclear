jQuery.fn.updateBlogrollPermissionsForm = function() {
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
			}
		}
		
		function admin(E,perms,re) {
					P = E.name.match(re);
					
					perms[P[1]]['blogroll'].checked = E.checked;
					perms[P[1]]['blogroll'].disabled = E.checked;
		}
	});
};

$(function() {
	$('#permissions-form').updateBlogrollPermissionsForm();
});