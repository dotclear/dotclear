<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup tags
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Tags');
        __('Tags for posts');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(App::backend()->menus()::MENU_BLOG, ['m' => 'tags'], '&m=tag(s|_posts)?(&.*)?$');

        App::behavior()->addBehaviors([
            'adminPostFormItems' => BackendBehaviors::tagsField(...),

            'adminAfterPostCreate' => BackendBehaviors::setTags(...),
            'adminAfterPostUpdate' => BackendBehaviors::setTags(...),

            'adminPostHeaders' => BackendBehaviors::postHeaders(...),

            'adminPostsActions' => BackendBehaviors::adminPostsActions(...),

            'adminPreferencesFormV2'       => BackendBehaviors::adminPreferenceForm(...),
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
