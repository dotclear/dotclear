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
use dcFavorites;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::BACKEND);

        if (!defined('DC_ANTISPAM_CONF_SUPER')) {
            define('DC_ANTISPAM_CONF_SUPER', false);
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        My::backendSidebarMenuIcon();

        dcCore::app()->addBehaviors([
            'coreAfterCommentUpdate'    => [Antispam::class, 'trainFilters'],
            'adminAfterCommentDesc'     => [Antispam::class, 'statusMessage'],
            'adminDashboardHeaders'     => [Antispam::class, 'dashboardHeaders'],
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
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
            dcCore::app()->addBehaviors([
                'adminBlogPreferencesFormV2'    => [BackendBehaviors::class, 'adminBlogPreferencesForm'],
                'adminBeforeBlogSettingsUpdate' => [BackendBehaviors::class, 'adminBeforeBlogSettingsUpdate'],
                'adminCommentsSpamFormV2'       => [BackendBehaviors::class, 'adminCommentsSpamForm'],
                'adminPageHelpBlock'            => [BackendBehaviors::class, 'adminPageHelpBlock'],
            ]);
        }

        return true;
    }
}
