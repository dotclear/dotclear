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
if (!defined('DC_RC_PATH')) {
    return;
}

require __DIR__ . '/class.widgets.php';

# Available widgets
dcCore::app()->widgets = new dcWidgets();

// Obsolete
$__widgets = dcCore::app()->widgets;

dcCore::app()->widgets
    ->create('search', __('Search engine'), ['defaultWidgets', 'search'], null, 'Search engine form')
    ->addTitle(__('Search'))
    ->setting('placeholder', __('Placeholder (HTML5 only, optional):'), '')
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

dcCore::app()->widgets
    ->create('navigation', __('Navigation links'), ['defaultWidgets', 'navigation'], null, 'List of navigation links')
    ->addTitle()
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

dcCore::app()->widgets
    ->create('bestof', __('Selected entries'), ['defaultWidgets', 'bestof'], null, 'List of selected entries')
    ->addTitle(__('Best of me'))
    ->setting('orderby', __('Sort:'), 'asc', 'combo', [__('Ascending') => 'asc', __('Descending') => 'desc'])
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

dcCore::app()->widgets
    ->create('langs', __('Blog languages'), ['defaultWidgets', 'langs'], null, 'List of available languages')
    ->addTitle(__('Languages'))
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

dcCore::app()->widgets
    ->create('categories', __('List of categories'), ['defaultWidgets', 'categories'], null, 'List of categories')
    ->addTitle(__('Categories'))
    ->setting('postcount', __('With entries counts'), 0, 'check')
    ->setting('subcatscount', __('Include sub cats in count'), false, 'check')
    ->setting('with_empty', __('Include empty categories'), 0, 'check')
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

dcCore::app()->widgets
    ->create('subscribe', __('Subscribe links'), ['defaultWidgets', 'subscribe'], null, 'Feed subscription links (RSS or Atom)')
    ->addTitle(__('Subscribe'))
    ->setting('type', __('Feeds type:'), 'atom', 'combo', ['Atom' => 'atom', 'RSS' => 'rss2'])
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

dcCore::app()->widgets->
    create('feed', __('Feed reader'), ['defaultWidgets', 'feed'], null, 'List of last entries from feed (RSS or Atom)')
    ->addTitle(__('Somewhere else'))
    ->setting('url', __('Feed URL:'), '')
    ->setting('limit', __('Entries limit:'), 10)
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

dcCore::app()->widgets
    ->create('text', __('Text'), ['defaultWidgets', 'text'], null, 'Simple text')
    ->addTitle()
    ->setting('text', __('Text:'), '', 'textarea')
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

$rs         = dcCore::app()->blog->getCategories(['post_type' => 'post']);
$categories = ['' => '', __('Uncategorized') => 'null'];
while ($rs->fetch()) {
    $categories[str_repeat('&nbsp;&nbsp;', $rs->level - 1) . ($rs->level - 1 == 0 ? '' : '&bull; ') . html::escapeHTML($rs->cat_title)] = $rs->cat_id;
}
$w = dcCore::app()->widgets->create('lastposts', __('Last entries'), ['defaultWidgets', 'lastposts'], null, 'List of last entries published');
$w
    ->addTitle(__('Last entries'))
    ->setting('category', __('Category:'), '', 'combo', $categories);
if (dcCore::app()->plugins->moduleExists('tags')) {
    $w->setting('tag', __('Tag:'), '');
}
$w
    ->setting('limit', __('Entries limit:'), 10)
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();
unset($rs, $categories, $w);

dcCore::app()->widgets
    ->create('lastcomments', __('Last comments'), ['defaultWidgets', 'lastcomments'], null, 'List of last comments published')
    ->addTitle(__('Last comments'))
    ->setting('limit', __('Comments limit:'), 10)
    ->addHomeOnly()
    ->addContentOnly()
    ->addClass()
    ->addOffline();

# --BEHAVIOR-- initWidgets
dcCore::app()->callBehavior('initWidgets', dcCore::app()->widgets);

# Default widgets
dcCore::app()->default_widgets = ['nav' => new dcWidgets(), 'extra' => new dcWidgets(), 'custom' => new dcWidgets()];

dcCore::app()->default_widgets['nav']->append(dcCore::app()->widgets->search);
dcCore::app()->default_widgets['nav']->append(dcCore::app()->widgets->bestof);
dcCore::app()->default_widgets['nav']->append(dcCore::app()->widgets->categories);
dcCore::app()->default_widgets['custom']->append(dcCore::app()->widgets->subscribe);

# --BEHAVIOR-- initDefaultWidgets
dcCore::app()->callBehavior('initDefaultWidgets', dcCore::app()->widgets, dcCore::app()->default_widgets);
