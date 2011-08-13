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

require dirname(__FILE__).'/class.widgets.php';

# Available widgets
global $__widgets;
$__widgets = new dcWidgets;

$__widgets->create('search',__('Search engine'),array('defaultWidgets','search'));
$__widgets->search->setting('title',__('Title:'),__('Search'));

$__widgets->create('navigation',__('Navigation links'),array('defaultWidgets','navigation'));
$__widgets->navigation->setting('title',__('Title:'),'');

$__widgets->create('bestof',__('Selected entries'),array('defaultWidgets','bestof'));
$__widgets->bestof->setting('title',__('Title:'),__('Best of me'));
$__widgets->bestof->setting('orderby',__('Sort:'),'asc','combo',array(__('Ascending') => 'asc', __('Descending') => 'desc'));
$__widgets->bestof->setting('homeonly',__('Home page only'),1,'check');

$__widgets->create('langs',__('Blog languages'),array('defaultWidgets','langs'));
$__widgets->langs->setting('title',__('Title:'),__('Languages'));
$__widgets->langs->setting('homeonly',__('Home page only'),1,'check');

$__widgets->create('categories',__('Categories list'),array('defaultWidgets','categories'));
$__widgets->categories->setting('title',__('Title:'),__('Categories'));
$__widgets->categories->setting('postcount',__('With entries counts'),0,'check');

$__widgets->create('subscribe',__('Subscribe links'),array('defaultWidgets','subscribe'));
$__widgets->subscribe->setting('title',__('Title:'),__('Subscribe'));
$__widgets->subscribe->setting('type',__('Feeds type:'),'atom','combo',array('Atom' => 'atom', 'RSS' => 'rss2'));
$__widgets->subscribe->setting('homeonly',__('Home page only'),0,'check');

$__widgets->create('feed',__('Feed reader'),array('defaultWidgets','feed'));
$__widgets->feed->setting('title',__('Title:'),__('Somewhere else'));
$__widgets->feed->setting('url',__('Feed URL:'),'');
$__widgets->feed->setting('limit',__('Entries limit:'),10);
$__widgets->feed->setting('homeonly',__('Home page only'),1,'check');

$__widgets->create('text',__('Text'),array('defaultWidgets','text'));
$__widgets->text->setting('title',__('Title:'),'');
$__widgets->text->setting('text',__('Text:'),'','textarea');
$__widgets->text->setting('homeonly',__('Home page only'),0,'check');

$__widgets->create('lastposts',__('Last entries'),array('defaultWidgets','lastposts'));
$__widgets->lastposts->setting('title',__('Title:'),__('Last entries'));
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
$__widgets->lastposts->setting('homeonly',__('Home page only'),1,'check');


$__widgets->create('lastcomments',__('Last comments'),array('defaultWidgets','lastcomments'));
$__widgets->lastcomments->setting('title',__('Title:'),__('Last comments'));
$__widgets->lastcomments->setting('limit',__('Comments limit:'),10);
$__widgets->lastcomments->setting('homeonly',__('Home page only'),1,'check');

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