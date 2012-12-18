/*
# ***** BEGIN LICENSE BLOCK *****
# This file is part of daInstaller, a plugin for DotClear2.
# Copyright (c) 2008-2011 Tomtom, Pep and contributors, for DotAddict.org.
# All rights reserved.
#
# ***** END LICENSE BLOCK *****
*/
$(function() {
	$('a.modal').modalWeb($(window).width()-60,$(window).height()-60);
});
$(function() {
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});
	dotclear.commentsActionsHelper();
});