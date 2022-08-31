<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
    __('Tags'),
    dcCore::app()->adminurl->get('admin.plugin.tags', ['m' => 'tags']),
    [dcPage::getPF('tags/icon.svg'), dcPage::getPF('tags/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.tags')) . '&m=tag(s|_posts)?(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id)
);

require __DIR__ . '/_widgets.php';

dcCore::app()->addBehavior('adminPostFormItems', ['tagsBehaviors', 'tagsField']);

dcCore::app()->addBehavior('adminAfterPostCreate', ['tagsBehaviors', 'setTags']);
dcCore::app()->addBehavior('adminAfterPostUpdate', ['tagsBehaviors', 'setTags']);

dcCore::app()->addBehavior('adminPostHeaders', ['tagsBehaviors', 'postHeaders']);

dcCore::app()->addBehavior('adminPostsActions', ['tagsBehaviors', 'adminPostsActionsPage']);

dcCore::app()->addBehavior('adminPreferencesFormV2', ['tagsBehaviors', 'adminUserForm']);
dcCore::app()->addBehavior('adminBeforeUserOptionsUpdate', ['tagsBehaviors', 'setTagListFormat']);

dcCore::app()->addBehavior('adminUserForm', ['tagsBehaviors', 'adminUserForm']);
dcCore::app()->addBehavior('adminBeforeUserCreate', ['tagsBehaviors', 'setTagListFormat']);
dcCore::app()->addBehavior('adminBeforeUserUpdate', ['tagsBehaviors', 'setTagListFormat']);

dcCore::app()->addBehavior('adminDashboardFavoritesV2', ['tagsBehaviors', 'dashboardFavorites']);

dcCore::app()->addBehavior('adminPageHelpBlock', ['tagsBehaviors', 'adminPageHelpBlock']);

dcCore::app()->addBehavior('adminPostEditor', ['tagsBehaviors', 'adminPostEditor']);
dcCore::app()->addBehavior('ckeditorExtraPlugins', ['tagsBehaviors', 'ckeditorExtraPlugins']);
