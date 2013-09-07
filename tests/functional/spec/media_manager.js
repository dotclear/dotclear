describe("Enhanced Media Manager", function() {
	describe("Starting with media manager enhanced disabled", function() {
		it("Enhanced uploader can be temporarily enabled", function() {
			loadFixtures('form_media_disabled.html');
			loadStyleFixtures('jsUpload/style.css');
			
			$('#fileupload').enhancedUploader();
			expect($('p.clear a.enhanced-toggle').text()).toBe(dotclear.msg.enhanced_uploader_activate);
			expect($('#fileupload .button.start')).not.toBeDisabled();

			$('p.clear a.enhanced-toggle').click();
			expect($('p.clear a.enhanced-toggle').text()).toBe(dotclear.msg.enhanced_uploader_disable);			
			expect($('.button.start')).toBeDisabled();
		});
	});

	describe("Starting with media manager enhanced enabled", function() {
		it("Enhanced uploader can be temporarily disabled", function() {
			loadFixtures('form_media_enabled.html');
			loadStyleFixtures('jsUpload/style.css');

			$('#fileupload').enhancedUploader();
			expect($('p.clear a.enhanced-toggle').text()).toBe(dotclear.msg.enhanced_uploader_disable);
			expect($('.button.start')).toBeDisabled();

			$('p.clear a.enhanced-toggle').click();
			expect($('p.clear a.enhanced-toggle').text()).toBe(dotclear.msg.enhanced_uploader_activate);
			expect($('#fileupload .button.start')).not.toBeDisabled();
		});
	});
});
