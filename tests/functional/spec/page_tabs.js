describe("tabs method (admin/js/pageTabs.js)", function() {
	it("Must construct tabs using div content", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');

		expect($('#user-options')).toBeVisible();
		expect($('#user-profile')).toBeVisible();
		expect($('#user-favorites')).toBeVisible();
		expect($('.part-tabs')).not.toExist();
 
		$.pageTabs('user-favorites');
		expect($('#part-user-options')).not.toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).toBeVisible();
 
		expect($('.part-tabs')).toExist();
		expect($('.part-tabs ul li#part-tabs-user-options')).toExist();
		expect($('.part-tabs ul li#part-tabs-user-profile')).toExist();
		expect($('.part-tabs ul li#part-tabs-user-favorites')).toExist();
		expect($('#part-tabs-user-favorites')).toHaveClass('part-tabs-active');
	});

	it("Must open first part if pageTabs called without argument", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');

		$.pageTabs();
		expect($('#part-user-options')).toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).not.toBeVisible();		
		expect($('#part-tabs-user-options')).toHaveClass('part-tabs-active');
	});
 
	it("Must change visible part when clicking another tab", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');
		
		$.pageTabs('user-options');
		expect($('#part-user-options')).toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).not.toBeVisible();

		$.pageTabs.clickTab('user-profile');
		expect($('#part-tabs-user-profile')).toHaveClass('part-tabs-active');		
		expect($('#part-user-options')).not.toBeVisible();
		expect($('#part-user-profile')).toBeVisible();
	});

	it("Must change opened part if corresponding anchor is in url", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');

		spyOn(jQuery.pageTabs, 'getLocationHash').andReturn('user-favorites');
		$.pageTabs();
		expect($('#part-user-options')).not.toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).toBeVisible();		
	});

	it("Must trigger event onetabload only the first time the tab is loaded", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');

		var user_option_count_call = user_profile_count_call = user_favorites_count_call = 0;
		spyOn(jQuery.fn, 'onetabload').andCallThrough();
		$('#user-options').onetabload(function() {user_option_count_call++;});
		$('#user-profile').onetabload(function() {user_profile_count_call++;});
		$('#user-favorites').onetabload(function() {user_favorites_count_call++;});

		$.pageTabs('user-options');
		expect(jQuery.fn.onetabload).toHaveBeenCalled();
		$.pageTabs.clickTab('user-profile');
		$.pageTabs.clickTab('user-options');

		expect(user_option_count_call).toBe(1);
		expect(user_profile_count_call).toBe(1);
		expect(user_favorites_count_call).toBe(0);
	});

	it("Must trigger event tabload every first time the tab is loaded", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');
		
		spyOn(jQuery.fn, 'tabload').andCallThrough();

		var user_option_count_call = user_profile_count_call = user_favorites_count_call = 0;
		$('#user-options').tabload(function() {user_option_count_call++;});
		$('#user-profile').tabload(function() {user_profile_count_call++;});
		$('#user-favorites').tabload(function() {user_favorites_count_call++;});
	
		$.pageTabs('user-options');
		$.pageTabs.clickTab('user-profile');
		$.pageTabs.clickTab('user-options');

		expect(user_option_count_call).toBe(2);
		expect(user_profile_count_call).toBe(1);
		expect(user_favorites_count_call).toBe(0);
	});

	it("Must keeps history of navigation in tabs", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');

		var navigation = ['user-options', 'user-profile', 'user-favorites'];
		var current_index = 0;

		$.pageTabs(navigation[current_index]);
		current_index++;
		expect($('#part-user-options')).toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).not.toBeVisible();
		
		$.pageTabs.clickTab(navigation[current_index]);
		current_index++;
		expect($('#part-user-options')).not.toBeVisible();
		expect($('#part-user-profile')).toBeVisible();
		expect($('#part-user-favorites')).not.toBeVisible();

		$.pageTabs.clickTab(navigation[current_index]);
		expect($('#part-user-options')).not.toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).toBeVisible();

		// simulate back : window.history.back();
		current_index--;
		spyOn(jQuery.pageTabs, 'getLocationHash').andReturn(navigation[current_index]);
		jQuery.event.trigger('hashchange');
		
		expect($('#part-user-options')).not.toBeVisible();
		expect($('#part-user-profile')).toBeVisible();
		expect($('#part-user-favorites')).not.toBeVisible();
	});

	it("Must open first tab when clicking back until hash is empty", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');

		var navigation = ['', 'user-profile', 'user-favorites'];
		var current_index = 0;

		$.pageTabs();
		current_index++;
		$.pageTabs.clickTab(navigation[current_index]);
		// tab is now user-profile

		// simulate back : window.history.back();
		current_index--;
		spyOn(jQuery.pageTabs, 'getLocationHash').andReturn(navigation[current_index]);
		jQuery.event.trigger('hashchange');

		expect($('#part-user-options')).toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).not.toBeVisible();
	});
});

