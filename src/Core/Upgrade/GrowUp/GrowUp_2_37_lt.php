<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\Core\Upgrade\Upgrade;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_37_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
            ],
            // Folders
            [
                // Some removed locales folders were missing from 2.36 cleanup in plugins and themes
                'plugins/*/locales/bn',
                'plugins/*/locales/ca',
                'plugins/*/locales/eo',
                'plugins/*/locales/es-ar',
                'plugins/*/locales/eu',
                'plugins/*/locales/hi',
                'plugins/*/locales/hr',
                'plugins/*/locales/lb',
                'plugins/*/locales/oc',
                'plugins/*/locales/sr',
                'plugins/*/locales/te',
                'themes/*/locales/bn',
                'themes/*/locales/ca',
                'themes/*/locales/eo',
                'themes/*/locales/es-ar',
                'themes/*/locales/eu',
                'themes/*/locales/hi',
                'themes/*/locales/hr',
                'themes/*/locales/lb',
                'themes/*/locales/oc',
                'themes/*/locales/sr',
                'themes/*/locales/te',
            ]
        );

        return $cleanup_sessions;
    }
}
