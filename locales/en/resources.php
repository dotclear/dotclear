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
if (!defined('DC_RC_PATH')) { return; }

$__resources['rss_news'] = 'http://dotclear.org/blog/feed/category/News/atom';

$__resources['doc'] = array(
	"Dotclear 2 documentation" => 'http://dotclear.org/documentation/2.0',
	'Dotclear 2 presentation' => 'http://dotclear.org/documentation/2.0/overview/tour',
	"User manual" => 'http://dotclear.org/documentation/2.0/usage',
	"Installation and administration guides" => 'http://dotclear.org/documentation/2.0/admin',
	"Dotclear 2 support forum" => 'http://forum.dotclear.net/'
);

$__resources['help'] = array(
	'core_blog_pref' => dirname(__FILE__).'/help/blog_pref.html',
	'core_categories' => dirname(__FILE__).'/help/categories.html',
	'core_comments' => dirname(__FILE__).'/help/comments.html',
	'core_media' => dirname(__FILE__).'/help/media.html',
	'core_post' => dirname(__FILE__).'/help/post.html',
	'core_posts' => dirname(__FILE__).'/help/posts.html',
	'core_user_pref' => dirname(__FILE__).'/help/user_pref.html',
	'core_user' => dirname(__FILE__).'/help/user.html',
	'core_wiki' => dirname(__FILE__).'/help/wiki.html'
);
?>