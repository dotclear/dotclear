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
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use dcAdmin;
use dcAuth;
use dcCore;
use dcNsProcess;
use dcPage;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            static::$init = true;
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
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

        dcCore::app()->addBehaviors([
            'adminPostFormItems' => [BackendBehaviors::class, 'tagsField'],

            'adminAfterPostCreate' => [BackendBehaviors::class, 'setTags'],
            'adminAfterPostUpdate' => [BackendBehaviors::class, 'setTags'],

            'adminPostHeaders' => [BackendBehaviors::class, 'postHeaders'],

            'adminPostsActions' => [BackendBehaviors::class, 'adminPostsActions'],

            'adminPreferencesFormV2'       => [BackendBehaviors::class, 'adminUserForm'],
            'adminBeforeUserOptionsUpdate' => [BackendBehaviors::class, 'setTagListFormat'],

            'adminUserForm'         => [BackendBehaviors::class, 'adminUserForm'],
            'adminBeforeUserCreate' => [BackendBehaviors::class, 'setTagListFormat'],
            'adminBeforeUserUpdate' => [BackendBehaviors::class, 'setTagListFormat'],

            'adminDashboardFavoritesV2' => [BackendBehaviors::class, 'dashboardFavorites'],

            'adminPageHelpBlock' => [BackendBehaviors::class, 'adminPageHelpBlock'],

            'adminPostEditor'      => [BackendBehaviors::class, 'adminPostEditor'],
            'ckeditorExtraPlugins' => [BackendBehaviors::class, 'ckeditorExtraPlugins'],

            'initWidgets' => [Widgets::class, 'initWidgets'],
        ]);

        return true;
    }
}
