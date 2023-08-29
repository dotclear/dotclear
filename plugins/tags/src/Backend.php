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

use Dotclear\Core\Core;
use Dotclear\Core\Backend\Menus;
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

        My::addBackendMenuItem(Menus::MENU_BLOG, ['m' => 'tags'], '&m=tag(s|_posts)?(&.*)?$');

        Core::behavior()->addBehaviors([
            'adminPostFormItems' => BackendBehaviors::tagsField(...),

            'adminAfterPostCreate' => BackendBehaviors::setTags(...),
            'adminAfterPostUpdate' => BackendBehaviors::setTags(...),

            'adminPostHeaders' => BackendBehaviors::postHeaders(...),

            'adminPostsActions' => BackendBehaviors::adminPostsActions(...),

            'adminPreferencesFormV2'       => BackendBehaviors::adminUserForm(...),
            'adminBeforeUserOptionsUpdate' => BackendBehaviors::setTagListFormat(...),

            'adminUserForm'         => BackendBehaviors::adminUserForm(...),
            'adminBeforeUserCreate' => BackendBehaviors::setTagListFormat(...),
            'adminBeforeUserUpdate' => BackendBehaviors::setTagListFormat(...),

            'adminDashboardFavoritesV2' => BackendBehaviors::dashboardFavorites(...),

            'adminPageHelpBlock' => BackendBehaviors::adminPageHelpBlock(...),

            'adminPostEditor'      => BackendBehaviors::adminPostEditor(...),
            'ckeditorExtraPlugins' => BackendBehaviors::ckeditorExtraPlugins(...),

            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
