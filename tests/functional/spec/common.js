describe("Others common methods (admin/js/common.js)", function() {
	describe("postsActionsHelper", function() {
		it("A confirm popup must appear if action is \"delete\" and at least on checkbox is checked", function() {
			loadFixtures('posts_list.html');
			dotclear.postsActionsHelper();
			
			spyOn(window, 'confirm').andReturn(false);
			var submitCallback = jasmine.createSpy().andReturn(false);
			$('#form-entries').submit(submitCallback);

			$('select[name="action"] option[value="delete"]').attr('selected', 'selected');
			$('input[type="checkbox"][name="entries[]"][value="2"]').attr('checked', 'checked');

			$('#form-entries').trigger('submit');
			expect(window.confirm).toHaveBeenCalled();
		});
		it("Confirm popup doesn't appear if action is \"delete\" but no checkbox is checked", function() {
			loadFixtures('posts_list.html');
			dotclear.postsActionsHelper();
			
			spyOn(window, 'confirm').andReturn(false);
			var submitCallback = jasmine.createSpy().andReturn(false);
			$('#form-entries').submit(submitCallback);

			$('select[name="action"] option[value="delete"]').attr('selected', 'selected');
			$('#form-entries').trigger('submit');
			expect(window.confirm).not.toHaveBeenCalled();
		});
		it("Others actions don't show confirm popup", function() {
			loadFixtures('posts_list.html');
			dotclear.postsActionsHelper();
			
			spyOn(window, 'confirm').andReturn(false);
			var submitCallback = jasmine.createSpy().andReturn(false);
			$('#form-entries').submit(submitCallback);

			$('select[name="action"] option[value="publish"]').attr('selected', 'selected');
			$('#form-entries').trigger('submit');
			expect(window.confirm).not.toHaveBeenCalled();
		});
	});

	describe("commentsActionsHelper", function() {
		it("A confirm popup must appear if action is \"delete\" and at least on checkbox is checked", function() {
			loadFixtures('comments_list.html');
			dotclear.commentsActionsHelper();
			
			spyOn(window, 'confirm').andReturn(false);
			var submitCallback = jasmine.createSpy().andReturn(false);
			$('#form-comments').submit(submitCallback);

			$('select[name="action"] option[value="delete"]').attr('selected', 'selected');
			$('input[type="checkbox"][name="comments[]"][value="2"]').attr('checked', 'checked');

			$('#form-comments').trigger('submit');
			expect(window.confirm).toHaveBeenCalled();
		});
		it("Confirm popup doesn't appear if action is \"delete\" but no checkbox is checked", function() {
			loadFixtures('comments_list.html');
			dotclear.commentsActionsHelper();
			
			spyOn(window, 'confirm').andReturn(false);
			var submitCallback = jasmine.createSpy().andReturn(false);
			$('#form-comments').submit(submitCallback);

			$('select[name="action"] option[value="delete"]').attr('selected', 'selected');
			$('#form-comments').trigger('submit');
			expect(window.confirm).not.toHaveBeenCalled();
		});
		it("Others actions don't show confirm popup", function() {
			loadFixtures('comments_list.html');
			dotclear.commentsActionsHelper();
			
			spyOn(window, 'confirm').andReturn(false);
			var submitCallback = jasmine.createSpy().andReturn(false);
			$('#form-comments').submit(submitCallback);

			$('select[name="action"] option[value="publish"]').attr('selected', 'selected');
			$('#form-comments').trigger('submit');
			expect(window.confirm).not.toHaveBeenCalled();
		});
	});

	describe("checkboxesHelpers", function() {
		it("Must add links to select all,none or invert selection", function() {
			loadFixtures('entries_list.html');
			dotclear.checkboxesHelpers($('.checkboxes-helpers', '#form-entries'));

			expect($('.checkboxes-helpers a', '#form-entries').length).toBe(3);
			expect($('.checkboxes-helpers a:eq(0)', '#form-entries').text()).toBe(dotclear.msg.select_all);
			expect($('.checkboxes-helpers a:eq(1)', '#form-entries').text()).toBe(dotclear.msg.no_selection);
			expect($('.checkboxes-helpers a:eq(2)', '#form-entries').text()).toBe(dotclear.msg.invert_sel);
		});

		it("Click all must select all checkboxes", function() {
			loadFixtures('entries_list.html');
			dotclear.checkboxesHelpers($('.checkboxes-helpers', '#form-entries'));
			
			$('.checkboxes-helpers a:eq(0)').click();
			expect($('#form-entries input[name="entries[]"]:checked').length).toBe(4);
		});

		it("Click 'no selection'  must uncheck all checkboxes", function() {
			loadFixtures('entries_list.html');
			dotclear.checkboxesHelpers($('.checkboxes-helpers', '#form-entries'));

			$('input[name="entries[]"]:eq(1)').click();
			$('input[name="entries[]"]:eq(3)').click();
			$('.checkboxes-helpers a:eq(1)').click();
			expect($('#form-entries input[name="entries[]"]:checked').length).toBe(0);
		});

		it("Click invert must select all uncheck checkboxes", function() {
			loadFixtures('entries_list.html');
			dotclear.checkboxesHelpers($('.checkboxes-helpers', '#form-entries'));
			
			$('input[name="entries[]"]:eq(1)').click();
			$('.checkboxes-helpers a:eq(2)').click();
			expect($('#form-entries input[name="entries[]"]:checked').length).toBe(3);
			expect($('input[name="entries[]"]:eq(0)')).toBeChecked();
			expect($('input[name="entries[]"]:eq(1)')).not.toBeChecked();
			expect($('input[name="entries[]"]:eq(2)')).toBeChecked();
			expect($('input[name="entries[]"]:eq(3)')).toBeChecked();
		});

		it("Must take in consideration checkbox added dynamically", function() {
			loadFixtures('entries_list.html');
			dotclear.checkboxesHelpers($('.checkboxes-helpers', '#form-entries'));

			$('<li><input type="checkbox" name="entries[]" value="5"/><p>title 5</p></li>').appendTo('#form-entries ul');

			$('.checkboxes-helpers a:eq(0)').click();
			expect($('#form-entries input[name="entries[]"]:checked').length).toBe(5);
		});
	});
});
