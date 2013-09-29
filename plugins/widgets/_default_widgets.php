<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

require dirname(__FILE__).'/class.widgets.php';

# Available widgets
global $__widgets;
$__widgets = new dcWidgets;

$__widgets->create('search',__('Search engine'),array('defaultWidgets','search'),null,'Search engine form');
$__widgets->search->setting('title',__('Title (optional)').' :',__('Search'));
$__widgets->search->setting('homeonly',__('Display on:'),0,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->search->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->search->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('navigation',__('Navigation links'),array('defaultWidgets','navigation'),null,'List of navigation links');
$__widgets->navigation->setting('title',__('Title (optional)').' :','');
$__widgets->navigation->setting('homeonly',__('Display on:'),0,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->navigation->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->navigation->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('bestof',__('Selected entries'),array('defaultWidgets','bestof'),null,'List of selected entries');
$__widgets->bestof->setting('title',__('Title (optional)').' :',__('Best of me'));
$__widgets->bestof->setting('orderby',__('Sort:'),'asc','combo',array(__('Ascending') => 'asc', __('Descending') => 'desc'));
$__widgets->bestof->setting('homeonly',__('Display on:'),1,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->bestof->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->bestof->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('langs',__('Blog languages'),array('defaultWidgets','langs'),null,'List of available languages');
$__widgets->langs->setting('title',__('Title (optional)').' :',__('Languages'));
$__widgets->langs->setting('homeonly',__('Display on:'),1,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->langs->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->langs->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('categories',__('Categories'),array('defaultWidgets','categories'),null,'List of categories');
$__widgets->categories->setting('title',__('Title (optional)').' :',__('Categories'));
$__widgets->categories->setting('postcount',__('With entries counts'),0,'check');
$__widgets->categories->setting('with_empty',__('Include empty categories'),0,'check');
$__widgets->categories->setting('homeonly',__('Display on:'),0,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->categories->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->categories->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('subscribe',__('Subscribe links'),array('defaultWidgets','subscribe'),null,'RSS or Atom feed subscription links');
$__widgets->subscribe->setting('title',__('Title (optional)').' :',__('Subscribe'));
$__widgets->subscribe->setting('type',__('Feeds type:'),'atom','combo',array('Atom' => 'atom', 'RSS' => 'rss2'));
$__widgets->subscribe->setting('homeonly',__('Display on:'),1,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->subscribe->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->subscribe->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('feed',__('Feed reader'),array('defaultWidgets','feed'),null,'Last entries from feed ( RSS or Atom )');
$__widgets->feed->setting('title',__('Title (optional)').' :',__('Somewhere else'));
$__widgets->feed->setting('url',__('Feed URL:'),'');
$__widgets->feed->setting('limit',__('Entries limit:'),10);
$__widgets->feed->setting('homeonly',__('Display on:'),1,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->feed->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->feed->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('text',__('Text'),array('defaultWidgets','text'),null,'Simple text');
$__widgets->text->setting('title',__('Title (optional)').' :','');
$__widgets->text->setting('text',__('Text:'),'','textarea');
$__widgets->text->setting('homeonly',__('Display on:'),0,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->text->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->text->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('lastposts',__('Last entries'),array('defaultWidgets','lastposts'),null,'List of last entries published');
$__widgets->lastposts->setting('title',__('Title (optional)').' :',__('Last entries'));
$rs = $core->blog->getCategories(array('post_type'=>'post'));
$categories = array('' => '', __('Uncategorized') => 'null');
while ($rs->fetch()) {
	$categories[str_repeat('&nbsp;&nbsp;',$rs->level-1).($rs->level-1 == 0 ? '' : '&bull; ').html::escapeHTML($rs->cat_title)] = $rs->cat_id;
}
$__widgets->lastposts->setting('category',__('Category:'),'','combo',$categories);
unset($rs,$categories);
if ($core->plugins->moduleExists('tags')) {
	$__widgets->lastposts->setting('tag',__('Tag:'),'');
}
$__widgets->lastposts->setting('limit',__('Entries limit:'),10);
$__widgets->lastposts->setting('homeonly',__('Display on:'),1,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->lastposts->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->lastposts->advanced_setting('class',__('CSS class:'),'');

$__widgets->create('lastcomments',__('Last comments'),array('defaultWidgets','lastcomments'),null,'List of last comments posted');
$__widgets->lastcomments->setting('title',__('Title (optional)').' :',__('Last comments'));
$__widgets->lastcomments->setting('limit',__('Comments limit:'),10);
$__widgets->lastcomments->setting('homeonly',__('Display on:'),1,'combo',
	array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->lastcomments->advanced_setting('content_only','',0,'radio', array(array(__('Content only'), '1'), array(__('Enclosing div'), '0')));
$__widgets->lastcomments->advanced_setting('class',__('CSS class:'),'');

# --BEHAVIOR-- initWidgets
$core->callBehavior('initWidgets',$__widgets);

# Default widgets
global $__default_widgets;
$__default_widgets = array('nav'=> new dcWidgets(), 'extra'=> new dcWidgets(), 'custom'=> new dcWidgets());

$__default_widgets['nav']->append($__widgets->search);
$__default_widgets['nav']->append($__widgets->navigation);
$__default_widgets['nav']->append($__widgets->bestof);
$__default_widgets['nav']->append($__widgets->categories);
$__default_widgets['extra']->append($__widgets->subscribe);

# --BEHAVIOR-- initDefaultWidgets
$core->callBehavior('initDefaultWidgets',$__widgets,$__default_widgets);
?>