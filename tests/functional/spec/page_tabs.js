describe("tabs method (admin/js/pageTabs.js)", function() {
	
	it("Construct tabs using div content", function() {
		
		loadFixtures('tabs.html');
		
		expect($('#tab-1')).toBeVisible();
		expect($('#tab-2')).toBeVisible();
		expect($('#tab-3')).toBeVisible();
		
		expect($('.part-tabs')).not.toExist();
		
		$.pageTabs('tab-1');
		
		expect($('#tab-1')).toBeVisible();
		expect($('#tab-2')).not.toBeVisible();
		expect($('#tab-3')).not.toBeVisible();
		
		expect($('.part-tabs ul li#part-tabs-tab-1 a[href=#tab-1]')).toExist();
		expect($('.part-tabs ul li#part-tabs-tab-2 a[href=#tab-2]')).toExist();
		expect($('.part-tabs ul li#part-tabs-tab-3 a[href=#tab-3]')).toExist();
	
		expect($('.part-tabs ul li#part-tabs-tab-1')).toHaveClass('part-tabs-active');
		
	});
	
	it("Change tabs when changing the hash", function() {
		
		runs(function() {        
            loadFixtures('tabs-iframe.html');
        });
		
		waitsFor(function() {
            return $("#testtab").contents().find('#tab-3').get(0);
        }, 10000);
		
		runs(function() {
			f$ = $("#testtab").get(0).contentWindow.$;
			f$.pageTabs('tab-1');

			expect(f$('#tab-1').get(0)).toBeVisible();
			expect(f$('#tab-2').get(0)).not.toBeVisible();
			
			expect(f$('.part-tabs ul li#part-tabs-tab-1').get(0)).toHaveClass('part-tabs-active');
			expect(f$('.part-tabs ul li#part-tabs-tab-2').get(0)).not.toHaveClass('part-tabs-active');

            $("#testtab").attr('src', $("#testtab").attr('src')+'#tab-2');
        });
		
		waitsFor(function() {
			f$ = $("#testtab").get(0).contentWindow.$;
            return f$('#tab-1').is(':not(:visible)');
        }, 10000);
		
		runs(function() {
			f$ = $("#testtab").get(0).contentWindow.$;
			
    		expect(f$('#tab-2').get(0)).toBeVisible();

			expect(f$('.part-tabs ul li#part-tabs-tab-1').get(0)).not.toHaveClass('part-tabs-active');
			expect(f$('.part-tabs ul li#part-tabs-tab-2').get(0)).toHaveClass('part-tabs-active');
			
        });
		
	});
	
	it("Load the correct tab with the correct hash", function() {
		
		runs(function() {        
            loadFixtures('tabs-iframe.html');
            $("#testtab").attr('src', $("#testtab").attr('src')+'#tab-3');
        });
		
		waitsFor(function() {
			cond1 = $("#testtab").attr('src').split('#')[1] != '';
			cond2 = $("#testtab").contents().find('#tab-3').get(0);
            return cond1 && cond2;
        }, 10000);
		
		runs(function() {
			f$ = $("#testtab").get(0).contentWindow.$;
			f$.pageTabs('tab-1');
			
			expect(f$('#tab-1').get(0)).not.toBeVisible();
			expect(f$('#tab-3').get(0)).toBeVisible();
			
			expect(f$('.part-tabs ul li#part-tabs-tab-1').get(0)).not.toHaveClass('part-tabs-active');
			expect(f$('.part-tabs ul li#part-tabs-tab-3').get(0)).toHaveClass('part-tabs-active');
        });
		
	});
});