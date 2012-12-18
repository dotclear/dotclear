$(function() {
	$('#new_auth_id').autocomplete(usersList, 
	{
		delay: 1000,
		matchSubset: true,
		matchContains: true
	});
});