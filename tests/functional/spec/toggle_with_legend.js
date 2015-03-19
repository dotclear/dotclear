describe("toggleWithLegend method (admin/js/common.js)", function() {
	it("Click arrow must make target visible", function() {
		loadStyleFixtures('default.css');
		loadFixtures('menu.html');
		$('#post_status').parent().toggleWithLegend($('#post_status'),{	});

		var $arrow = $('#post_status').parent().find('img');
		$arrow.click();
		expect($('#post_status')).toBeVisible();
	});
	it("Click arrow twice,must make target visible and after second click hidden", function() {
		loadFixtures('menu.html');
		loadStyleFixtures('default.css');
		$('#post_status').parent().toggleWithLegend($('#post_status'),{	});

		var $arrow = $('#post_status').parent().find('img');
		$arrow.click();
		expect($('#post_status')).toBeVisible();

		$arrow.click();
		expect($('#post_status')).toBeHidden();
	});
	it("Chick target must not hide target", function() {
		loadFixtures('menu.html');
		loadStyleFixtures('default.css');
		$('#post_status').parent().toggleWithLegend($('#post_status'),{	});

		var $arrow = $('#post_status').parent().find('img');
		$arrow.click();
		expect($('#post_status')).toBeVisible();

		$('#post_status option[value="-2"]').attr('selected', 'selected');
		expect($('#post_status')).toBeVisible();
	});
	it("Chick target must not hide target, when legend_click is true", function() {
		loadFixtures('menu.html');
		loadStyleFixtures('default.css');
		var $label = $('#post_status').parent().children('label');
		$label.toggleWithLegend($('#post_status'),{'legend_click':true, a_container:false});

		$label.click();
		expect($('#post_status')).toBeVisible();

		var $arrow = $('#post_status').parent().find('img');
		$arrow.click();
		expect($('#post_status')).toBeVisible();

		$('#post_status').val(-2).trigger('change');
		expect($('#post_status')).toBeVisible();
	});
});
