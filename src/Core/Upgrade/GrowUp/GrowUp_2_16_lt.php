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
use Dotclear\Database\Statement\InsertStatement;
use Dotclear\Database\Statement\UpdateStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_16_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Update DotAddict plugins store URL
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->set('setting_value = REPLACE(setting_value, ' . $sql->quote('http://update.dotaddict.org') . ', ' . $sql->quote('https://update.dotaddict.org') . ')')
            ->where('setting_id = ' . $sql->quote('store_plugin_url'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->update();

        // Update DotAddict themes store URL
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->set('setting_value = REPLACE(setting_value, ' . $sql->quote('http://update.dotaddict.org') . ', ' . $sql->quote('https://update.dotaddict.org') . ')')
            ->where('setting_id = ' . $sql->quote('store_theme_url'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->update();

        // Update CSP img-src default directive for media.dotaddict.org
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->set('setting_value = REPLACE(setting_value, ' . $sql->quote('http://media.dotaddict.org') . ', ' . $sql->quote('https://media.dotaddict.org') . ')')
            ->where('setting_id = ' . $sql->quote('csp_admin_img'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->update();

        // Set default jQuery loading for blog
        $sql = new InsertStatement();
        $sql
            ->into(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns(['setting_id', 'setting_ns', 'setting_value', 'setting_type', 'setting_label'])
            ->values([['jquery_needed', 'system', (string) true, 'boolean', 'Load jQuery library']])
            ->insert();

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                // jQuery farbtastic Color picker
                'admin/js/color-picker.js',
                'admin/js/jquery/jquery.farbtastic.js',
                'admin/style/farbtastic/farbtastic.css',
                'admin/style/farbtastic/marker.png',
                'admin/style/farbtastic/mask.png',
                'admin/style/farbtastic/wheel.png',
            ],
            // Folders
            [
                // jQuery farbtastic Color picker
                'admin/style/farbtastic',
            ]
        );

        return $cleanup_sessions;
    }
}
