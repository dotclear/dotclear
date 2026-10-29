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
use Dotclear\Database\Statement\UpdateStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_37_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Switch from DotAddict to Dotclear for plugins/themes repositories

        // Update CSP img-src default directive for media.dotaddict.org
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->set('setting_value = REPLACE(setting_value, ' . $sql->quote('https://media.dotaddict.org') . ', ' . $sql->quote('https://dotclear.org') . ')')
            ->where('setting_id = ' . $sql->quote('csp_admin_img'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->update();

        // Update plugins store URL
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->set('setting_value = REPLACE(setting_value, ' . $sql->quote('https://update.dotaddict.org/dc2/plugins.xml') . ', ' . $sql->quote('https://dotclear.org/plugin/store/dcstore.xml') . ')')
            ->where('setting_id = ' . $sql->quote('store_plugin_url'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->update();

        // Update themes store URL
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->set('setting_value = REPLACE(setting_value, ' . $sql->quote('https://update.dotaddict.org/dc2/themes.xml') . ', ' . $sql->quote('https://dotclear.org/theme/store/dcstore.xml') . ')')
            ->where('setting_id = ' . $sql->quote('store_theme_url'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->update();

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
                // Switch to jQuery 4.0.0
                'inc/js/jquery/3.7.1',
            ]
        );

        return $cleanup_sessions;
    }
}
