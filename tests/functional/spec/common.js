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
});
