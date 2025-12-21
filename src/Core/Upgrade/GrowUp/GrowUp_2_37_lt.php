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

use Dotclear\App;
use Dotclear\Core\Upgrade\Upgrade;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_37_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Switch from DotAddict to Dotclear for plugins/themes repositories
        // Update CSP img-src default directive for media.dotaddict.org
        $strReq = 'UPDATE ' . App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = REPLACE(setting_value, 'https://media.dotaddict.org', 'https://dotclear.org') " .
            " WHERE setting_id = 'csp_admin_img' " .
            " AND setting_ns = 'system' ";
        App::db()->con()->execute($strReq);
        // Update plugins store URL
        $strReq = 'UPDATE ' . App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = REPLACE(setting_value, 'https://update.dotaddict.org/dc2/plugins.xml', 'https://dotclear.org/plugin/store/dcstore.xml') " .
            " WHERE setting_id = 'store_plugin_url' " .
            " AND setting_ns = 'system' ";
        App::db()->con()->execute($strReq);
        // Update themes store URL
        $strReq = 'UPDATE ' . App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = REPLACE(setting_value, 'https://update.dotaddict.org/dc2/themes.xml', 'https://dotclear.org/theme/store/dcstore.xml') " .
            " WHERE setting_id = 'store_theme_url' " .
            " AND setting_ns = 'system' ";
        App::db()->con()->execute($strReq);

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
