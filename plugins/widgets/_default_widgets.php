<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

require dirname(__FILE__) . '/class.widgets.php';

# Available widgets
global $__widgets;
$__widgets = new dcWidgets;

$__widgets->create('search', __('Search engine'), array('defaultWidgets', 'search'), null, 'Search engine form');
$__widgets->search->setting('title', __('Title (optional)') . ' :', __('Search'));
$__widgets->search->setting('placeholder', __('Placeholder (HTML5 only, optional):'), '');
$__widgets->search->setting('homeonly', __('Display on:'), 0, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->search->setting('content_only', __('Content only'), 0, 'check');
$__widgets->search->setting('class', __('CSS class:'), '');
$__widgets->search->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('navigation', __('Navigation links'), array('defaultWidgets', 'navigation'), null, 'List of navigation links');
$__widgets->navigation->setting('title', __('Title (optional)') . ' :', '');
$__widgets->navigation->setting('homeonly', __('Display on:'), 0, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->navigation->setting('content_only', __('Content only'), 0, 'check');
$__widgets->navigation->setting('class', __('CSS class:'), '');
$__widgets->navigation->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('bestof', __('Selected entries'), array('defaultWidgets', 'bestof'), null, 'List of selected entries');
$__widgets->bestof->setting('title', __('Title (optional)') . ' :', __('Best of me'));
$__widgets->bestof->setting('orderby', __('Sort:'), 'asc', 'combo', array(__('Ascending') => 'asc', __('Descending') => 'desc'));
$__widgets->bestof->setting('homeonly', __('Display on:'), 1, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->bestof->setting('content_only', __('Content only'), 0, 'check');
$__widgets->bestof->setting('class', __('CSS class:'), '');
$__widgets->bestof->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('langs', __('Blog languages'), array('defaultWidgets', 'langs'), null, 'List of available languages');
$__widgets->langs->setting('title', __('Title (optional)') . ' :', __('Languages'));
$__widgets->langs->setting('homeonly', __('Display on:'), 1, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->langs->setting('content_only', __('Content only'), 0, 'check');
$__widgets->langs->setting('class', __('CSS class:'), '');
$__widgets->langs->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('categories', __('List of categories'), array('defaultWidgets', 'categories'), null, 'List of categories');
$__widgets->categories->setting('title', __('Title (optional)') . ' :', __('Categories'));
$__widgets->categories->setting('postcount', __('With entries counts'), 0, 'check');
$__widgets->categories->setting('subcatscount', __('Include sub cats in count'), false, 'check');
$__widgets->categories->setting('with_empty', __('Include empty categories'), 0, 'check');
$__widgets->categories->setting('homeonly', __('Display on:'), 0, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->categories->setting('content_only', __('Content only'), 0, 'check');
$__widgets->categories->setting('class', __('CSS class:'), '');
$__widgets->categories->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('subscribe', __('Subscribe links'), array('defaultWidgets', 'subscribe'), null, 'Feed subscription links (RSS or Atom)');
$__widgets->subscribe->setting('title', __('Title (optional)') . ' :', __('Subscribe'));
$__widgets->subscribe->setting('type', __('Feeds type:'), 'atom', 'combo', array('Atom' => 'atom', 'RSS' => 'rss2'));
$__widgets->subscribe->setting('homeonly', __('Display on:'), 1, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->subscribe->setting('content_only', __('Content only'), 0, 'check');
$__widgets->subscribe->setting('class', __('CSS class:'), '');
$__widgets->subscribe->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('feed', __('Feed reader'), array('defaultWidgets', 'feed'), null, 'List of last entries from feed (RSS or Atom)');
$__widgets->feed->setting('title', __('Title (optional)') . ' :', __('Somewhere else'));
$__widgets->feed->setting('url', __('Feed URL:'), '');
$__widgets->feed->setting('limit', __('Entries limit:'), 10);
$__widgets->feed->setting('homeonly', __('Display on:'), 1, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->feed->setting('content_only', __('Content only'), 0, 'check');
$__widgets->feed->setting('class', __('CSS class:'), '');
$__widgets->feed->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('text', __('Text'), array('defaultWidgets', 'text'), null, 'Simple text');
$__widgets->text->setting('title', __('Title (optional)') . ' :', '');
$__widgets->text->setting('text', __('Text:'), '', 'textarea');
$__widgets->text->setting('homeonly', __('Display on:'), 0, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->text->setting('content_only', __('Content only'), 0, 'check');
$__widgets->text->setting('class', __('CSS class:'), '');
$__widgets->text->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('lastposts', __('Last entries'), array('defaultWidgets', 'lastposts'), null, 'List of last entries published');
$__widgets->lastposts->setting('title', __('Title (optional)') . ' :', __('Last entries'));
$rs         = $core->blog->getCategories(array('post_type' => 'post'));
$categories = array('' => '', __('Uncategorized') => 'null');
while ($rs->fetch()) {
    $categories[str_repeat('&nbsp;&nbsp;', $rs->level - 1) . ($rs->level - 1 == 0 ? '' : '&bull; ') . html::escapeHTML($rs->cat_title)] = $rs->cat_id;
}
$__widgets->lastposts->setting('category', __('Category:'), '', 'combo', $categories);
unset($rs, $categories);
if ($core->plugins->moduleExists('tags')) {
    $__widgets->lastposts->setting('tag', __('Tag:'), '');
}
$__widgets->lastposts->setting('limit', __('Entries limit:'), 10);
$__widgets->lastposts->setting('homeonly', __('Display on:'), 1, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->lastposts->setting('content_only', __('Content only'), 0, 'check');
$__widgets->lastposts->setting('class', __('CSS class:'), '');
$__widgets->lastposts->setting('offline', __('Offline'), 0, 'check');

$__widgets->create('lastcomments', __('Last comments'), array('defaultWidgets', 'lastcomments'), null, 'List of last comments published');
$__widgets->lastcomments->setting('title', __('Title (optional)') . ' :', __('Last comments'));
$__widgets->lastcomments->setting('limit', __('Comments limit:'), 10);
$__widgets->lastcomments->setting('homeonly', __('Display on:'), 1, 'combo',
    array(__('All pages') => 0, __('Home page only') => 1, __('Except on home page') => 2));
$__widgets->lastcomments->setting('content_only', __('Content only'), 0, 'check');
$__widgets->lastcomments->setting('class', __('CSS class:'), '');
$__widgets->lastcomments->setting('offline', __('Offline'), 0, 'check');

# --BEHAVIOR-- initWidgets
$core->callBehavior('initWidgets', $__widgets);

# Default widgets
global $__default_widgets;
$__default_widgets = array('nav' => new dcWidgets(), 'extra' => new dcWidgets(), 'custom' => new dcWidgets());

$__default_widgets['nav']->append($__widgets->search);
$__default_widgets['nav']->append($__widgets->bestof);
$__default_widgets['nav']->append($__widgets->categories);
$__default_widgets['custom']->append($__widgets->subscribe);

# --BEHAVIOR-- initDefaultWidgets
$core->callBehavior('initDefaultWidgets', $__widgets, $__default_widgets);
