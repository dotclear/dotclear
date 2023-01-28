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

dcCore::app()->addBehaviors([
    'adminPostFormItems'           => [tagsBehaviors::class, 'tagsField'],

    'adminAfterPostCreate'         => [tagsBehaviors::class, 'setTags'],
    'adminAfterPostUpdate'         => [tagsBehaviors::class, 'setTags'],

    'adminPostHeaders'             => [tagsBehaviors::class, 'postHeaders'],

    'adminPostsActions'            => [tagsBehaviors::class, 'adminPostsActions'],

    'adminPreferencesFormV2'       => [tagsBehaviors::class, 'adminUserForm'],
    'adminBeforeUserOptionsUpdate' => [tagsBehaviors::class, 'setTagListFormat'],

    'adminUserForm'                => [tagsBehaviors::class, 'adminUserForm'],
    'adminBeforeUserCreate'        => [tagsBehaviors::class, 'setTagListFormat'],
    'adminBeforeUserUpdate'        => [tagsBehaviors::class, 'setTagListFormat'],

    'adminDashboardFavoritesV2'    => [tagsBehaviors::class, 'dashboardFavorites'],

    'adminPageHelpBlock'           => [tagsBehaviors::class, 'adminPageHelpBlock'],

    'adminPostEditor'              => [tagsBehaviors::class, 'adminPostEditor'],
    'ckeditorExtraPlugins'         => [tagsBehaviors::class, 'ckeditorExtraPlugins'],
]);
