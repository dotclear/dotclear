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
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_USAGE,
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)
);

require __DIR__ . '/_widgets.php';

dcCore::app()->addBehavior('adminPostFormItems', [tagsBehaviors::class, 'tagsField']);

dcCore::app()->addBehavior('adminAfterPostCreate', [tagsBehaviors::class, 'setTags']);
dcCore::app()->addBehavior('adminAfterPostUpdate', [tagsBehaviors::class, 'setTags']);

dcCore::app()->addBehavior('adminPostHeaders', [tagsBehaviors::class, 'postHeaders']);

dcCore::app()->addBehavior('adminPostsActions', [tagsBehaviors::class, 'adminPostsActions']);

dcCore::app()->addBehavior('adminPreferencesFormV2', [tagsBehaviors::class, 'adminUserForm']);
dcCore::app()->addBehavior('adminBeforeUserOptionsUpdate', [tagsBehaviors::class, 'setTagListFormat']);

dcCore::app()->addBehavior('adminUserForm', [tagsBehaviors::class, 'adminUserForm']);
dcCore::app()->addBehavior('adminBeforeUserCreate', [tagsBehaviors::class, 'setTagListFormat']);
dcCore::app()->addBehavior('adminBeforeUserUpdate', [tagsBehaviors::class, 'setTagListFormat']);

dcCore::app()->addBehavior('adminDashboardFavoritesV2', [tagsBehaviors::class, 'dashboardFavorites']);

dcCore::app()->addBehavior('adminPageHelpBlock', [tagsBehaviors::class, 'adminPageHelpBlock']);

dcCore::app()->addBehavior('adminPostEditor', [tagsBehaviors::class, 'adminPostEditor']);
dcCore::app()->addBehavior('ckeditorExtraPlugins', [tagsBehaviors::class, 'ckeditorExtraPlugins']);
