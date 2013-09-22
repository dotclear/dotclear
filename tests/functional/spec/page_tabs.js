describe("tabs method (admin/js/pageTabs.js)", function() {
	it("Must construct tabs using div content", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');

		expect($('#user-options')).toBeVisible();
		expect($('#user-profile')).toBeVisible();
		expect($('#user-favorites')).toBeVisible();
		expect($('.part-tabs')).not.toExist();
 
		$.pageTabs('user-options');
		expect($('#part-user-options')).toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).not.toBeVisible();
 
		expect($('.part-tabs')).toExist();
		expect($('.part-tabs ul li#part-tabs-user-options')).toExist();
		expect($('.part-tabs ul li#part-tabs-user-profile')).toExist();
		expect($('.part-tabs ul li#part-tabs-user-favorites')).toExist();
		expect($('#part-tabs-user-options')).toHaveClass('part-tabs-active');
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

		$('.part-tabs ul li a[href="#user-profile"]').click();
		expect($('#part-tabs-user-profile')).toHaveClass('part-tabs-active');		
		expect($('#part-user-options')).not.toBeVisible();
		expect($('#part-user-profile')).toBeVisible();
	});

	it("Must change opened part if corresponding anchor is in url", function() {
		loadFixtures('tabs.html');
		loadStyleFixtures('default.css');

		spyOn(jQuery, 'pageTabsGetHash').andReturn('user-favorites');
		$.pageTabs();
		expect($('#part-user-options')).not.toBeVisible();
		expect($('#part-user-profile')).not.toBeVisible();
		expect($('#part-user-favorites')).toBeVisible();		
	});
});

