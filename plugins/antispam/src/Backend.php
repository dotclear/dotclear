<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use ArrayObject;
use dcCore;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        self::status(My::checkContext(My::BACKEND));

        // Dead but useful code (for l10n)
        __('Antispam') . __('Generic antispam plugin for Dotclear');

        if (!defined('DC_ANTISPAM_CONF_SUPER')) {
            define('DC_ANTISPAM_CONF_SUPER', false);
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        dcCore::app()->behavior->addBehaviors([
            'coreAfterCommentUpdate'    => Antispam::trainFilters(...),
            'adminAfterCommentDesc'     => Antispam::statusMessage(...),
            'adminDashboardHeaders'     => Antispam::dashboardHeaders(...),
            'adminDashboardFavoritesV2' => function (Favorites $favs) {
                $favs->register(
                    My::id(),
                    [
                        'title'       => My::name(),
                        'url'         => My::manageUrl(),
                        'small-icon'  => My::icons(),
                        'large-icon'  => My::icons(),
                        'permissions' => dcCore::app()->auth->makePermissions([
                            dcCore::app()->auth::PERMISSION_ADMIN,
                        ]), ]
                );
            },
            'adminDashboardFavsIconV2' => function (string $name, ArrayObject $icon) {
                // Check if it is comments favs
                if ($name === 'comments') {
                    // Hack comments title if there is at least one spam
                    $str = Antispam::dashboardIconTitle();
                    if ($str !== '') {
                        $icon[0] .= $str;
                    }
                }
            },
        ]);

        if (!DC_ANTISPAM_CONF_SUPER || dcCore::app()->auth->isSuperAdmin()) {
            dcCore::app()->behavior->addBehaviors([
                'adminBlogPreferencesFormV2'    => BackendBehaviors::adminBlogPreferencesForm(...),
                'adminBeforeBlogSettingsUpdate' => BackendBehaviors::adminBeforeBlogSettingsUpdate(...),
                'adminCommentsSpamFormV2'       => BackendBehaviors::adminCommentsSpamForm(...),
                'adminPageHelpBlock'            => BackendBehaviors::adminPageHelpBlock(...),
            ]);
        }

        return true;
    }
}
