<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * Dotclear upgrade procedure.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use dcNamespace;
use Dotclear\Core\Core;
use Dotclear\Core\Upgrade\Upgrade;

class GrowUp_2_16_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Update DotAddict plugins store URL
        $strReq = 'UPDATE ' . Core::con()->prefix() . dcNamespace::NS_TABLE_NAME .
            " SET setting_value = REPLACE(setting_value, 'http://update.dotaddict.org', 'https://update.dotaddict.org') " .
            " WHERE setting_id = 'store_plugin_url' " .
            " AND setting_ns = 'system' ";
        Core::con()->execute($strReq);
        // Update DotAddict themes store URL
        $strReq = 'UPDATE ' . Core::con()->prefix() . dcNamespace::NS_TABLE_NAME .
            " SET setting_value = REPLACE(setting_value, 'http://update.dotaddict.org', 'https://update.dotaddict.org') " .
            " WHERE setting_id = 'store_theme_url' " .
            " AND setting_ns = 'system' ";
        Core::con()->execute($strReq);
        // Update CSP img-src default directive for media.dotaddict.org
        $strReq = 'UPDATE ' . Core::con()->prefix() . dcNamespace::NS_TABLE_NAME .
            " SET setting_value = REPLACE(setting_value, 'http://media.dotaddict.org', 'https://media.dotaddict.org') " .
            " WHERE setting_id = 'csp_admin_img' " .
            " AND setting_ns = 'system' ";
        Core::con()->execute($strReq);
        // Set default jQuery loading for blog
        $strReq = 'INSERT INTO ' . Core::con()->prefix() . dcNamespace::NS_TABLE_NAME .
            ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
            ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
        Core::con()->execute(
            sprintf($strReq, 'jquery_needed', (string) true, 'boolean', 'Load jQuery library')
        );

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
