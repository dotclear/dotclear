<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::checkSuper();

$blog_id = '';
$blog_url = '';
$blog_name = '';
$blog_desc = '';

# Create a blog
if (!isset($_POST['id']) && !empty($_POST['blog_id']))
{
	$cur = $core->con->openCursor($core->prefix.'blog');
	$blog_id = $cur->blog_id = $_POST['blog_id'];
	$blog_url = $cur->blog_url = $_POST['blog_url'];
	$blog_name = $cur->blog_name = $_POST['blog_name'];
	$blog_desc = $cur->blog_desc = $_POST['blog_desc'];
	
	try
	{
		# --BEHAVIOR-- adminBeforeBlogCreate
		$core->callBehavior('adminBeforeBlogCreate',$cur,$blog_id);
		
		$core->addBlog($cur);
		
		# Default settings and override some
		$core->blogDefaults($cur->blog_id);
		$blog_settings = new dcSettings($core,$cur->blog_id);
		$blog_settings->addNamespace('system');
		$blog_settings->system->put('lang',$core->auth->getInfo('user_lang'));
		$blog_settings->system->put('blog_timezone',$core->auth->getInfo('user_tz'));
		
		if (substr($blog_url,-1) == '?') {
			$blog_settings->system->put('url_scan','query_string');
		} else {
			$blog_settings->system->put('url_scan','path_info');
		}
		
		# --BEHAVIOR-- adminAfterBlogCreate
		$core->callBehavior('adminAfterBlogCreate',$cur,$blog_id,$blog_settings);
		
		http::redirect('blog.php?id='.$cur->blog_id.'&add=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

if (!empty($_REQUEST['id']))
{
	$edit_blog_mode = true;
	include dirname(__FILE__).'/blog_pref.php';
}
else
{
	dcPage::open(__('New blog'),dcPage::jsConfirmClose('blog-form'));
	
	echo
	'<h2><a href="blogs.php">'.__('Blogs').'</a> &rsaquo; <span class="page-title">'.__('New blog').'</span></h2>'.
	
	'<form action="blog.php" method="post" id="blog-form" class="fieldset">'.
	
	$core->formNonce().
	'<p><label class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Blog ID:').' '.
	form::field('blog_id',30,32,html::escapeHTML($blog_id)).'</label></p>'.
	'<p class="form-note">'.__('At least 2 characters using letters, numbers or symbols.').'</p> '.
	'<p class="form-note warn">'.__('Please note that changing your blog ID may require changes in your public index.php file.').'</p>'.
	
	'<p><label class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Blog name:').' '.
	form::field('blog_name',30,255,html::escapeHTML($blog_name)).'</label></p>'.
	
	'<p><label class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Blog URL:').' '.
	form::field('blog_url',30,255,html::escapeHTML($blog_url)).'</label></p>'.
	
	'<p class="area"><label for="blog_desc">'.__('Blog description:').'</label> '.
	form::textarea('blog_desc',60,5,html::escapeHTML($blog_desc)).'</p>'.
	
	'<p><input type="submit" accesskey="s" value="'.__('Create').'" /></p>'.
	'</form>';

	dcPage::close();
}
?>