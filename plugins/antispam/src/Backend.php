<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup antispam
 */
class Backend extends Process
{
    public static function init(): bool
    {
        self::status(My::checkContext(My::BACKEND));

        // Dead but useful code (for l10n)
        __('Antispam');
        __('Generic antispam plugin for Dotclear');

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

        App::behavior()->addBehaviors([
            'coreAfterCommentUpdate'    => Antispam::trainFilters(...),
            'adminAfterCommentDesc'     => Antispam::statusMessage(...),
            'adminDashboardHeaders'     => Antispam::dashboardHeaders(...),
            'adminDashboardFavoritesV2' => function (Favorites $favs): string {
                $favs->register(
                    My::id(),
                    [
                        'title'       => My::name(),
                        'url'         => My::manageUrl(),
                        'small-icon'  => My::icons(),
                        'large-icon'  => My::icons(),
                        'permissions' => App::auth()->makePermissions([
                            App::auth()::PERMISSION_ADMIN,
                        ]), ]
                );

                return '';
            },
            'adminDashboardFavsIconV2' => function (string $name, ArrayObject $icon): string {
                // Check if it is comments favs
                if ($name === 'comments') {
                    // Hack comments title if there is at least one spam
                    $str = Antispam::dashboardIconTitle();
                    if ($str !== '') {
                        $icon[0] .= $str;
                    }
                }

                return '';
            },
        ]);

        if ((defined('DC_ANTISPAM_CONF_SUPER') && !constant('DC_ANTISPAM_CONF_SUPER')) || App::auth()->isSuperAdmin()) {
            App::behavior()->addBehaviors([
                'adminBlogPreferencesFormV2'    => BackendBehaviors::adminBlogPreferencesForm(...),
                'adminBeforeBlogSettingsUpdate' => BackendBehaviors::adminBeforeBlogSettingsUpdate(...),
                'adminCommentsSpamFormV2'       => BackendBehaviors::adminCommentsSpamForm(...),
                'adminPageHelpBlock'            => BackendBehaviors::adminPageHelpBlock(...),
            ]);
        }

        return true;
    }
}
