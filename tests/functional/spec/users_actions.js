describe("updatePermissionsForm method (admin/js/_users_actions.js)", function() {
	it("Click admin persmission must checked all associated permissions", function() {
		loadFixtures('normal.html');
		$('#permissions-form').updatePermissionsForm();
		var permissions = ['usage','publish','delete','contentadmin','categories'];

		$('input[name="perm[default][admin]"]').click();
		for (var _i=0,_len=permissions.length;_i<_len;_i++) {
			expect($('input[name="perm\\[default\\]\\['+permissions[_i]+'\\]"]')).toBeChecked();
			expect($('input[name="perm\\[default\\]\\['+permissions[_i]+'\\]"]')).toBeDisabled();
		}
	});

	it("Click contentadmin persmission must checked all associated permissions", function() {
		loadFixtures('normal.html');
		$('#permissions-form').updatePermissionsForm();
		var permissions = ['usage','publish','delete'];

		$('input[name="perm[default][contentadmin]"]').click();
		for (var _i=0,_len=permissions.length;_i<_len;_i++) {
			expect($('input[name="perm[default]['+permissions[_i]+']"]')).toBeChecked();
			expect($('input[name="perm[default]['+permissions[_i]+']"]')).toBeDisabled();
		}
	});
});
