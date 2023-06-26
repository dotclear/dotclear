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

use dcCore;
use Dotclear\Core\Backend\Utility;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Tags') . __('Tags for posts');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(Utility::MENU_BLOG, ['m' => 'tags'], '&m=tag(s|_posts)?(&.*)?$');

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
