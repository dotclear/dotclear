<?php

/**
 * @package     Dotclear
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
class GrowUp_2_11_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Some new settings should be initialized, prepare db queries
        $strReq = 'INSERT INTO ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
            ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
        App::con()->execute(
            sprintf($strReq, 'csp_admin_report_only', (string) false, 'boolean', 'CSP Report only violations (admin)')
        );

        // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
        // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
        $csp_prefix = App::con()->driver() === 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks driver
        $csp_suffix = App::con()->driver() === 'sqlite' ? ' 127.0.0.1' : ''; // Hack for SQlite Clearbricks driver

        # Try to fix some CSP directive wrongly stored for SQLite drivers
        $strReq = 'UPDATE ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = '" . $csp_prefix . "''self''" . $csp_suffix . "' " .
            " WHERE setting_id = 'csp_admin_default' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = 'self' ";
        App::con()->execute($strReq);
        $strReq = 'UPDATE ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = '" . $csp_prefix . "''self'' ''unsafe-inline'' ''unsafe-eval''" . $csp_suffix . "' " .
            " WHERE setting_id = 'csp_admin_script' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = 'self'' ''unsafe-inline'' ''unsafe-eval' ";
        App::con()->execute($strReq);
        $strReq = 'UPDATE ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = '" . $csp_prefix . "''self'' ''unsafe-inline''" . $csp_suffix . "' " .
            " WHERE setting_id = 'csp_admin_style' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = 'self'' ''unsafe-inline' ";
        App::con()->execute($strReq);
        $strReq = 'UPDATE ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = '" . $csp_prefix . "''self'' data: media.dotaddict.org blob:' " .
            " WHERE setting_id = 'csp_admin_img' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = 'self'' data: media.dotaddict.org' ";
        App::con()->execute($strReq);

        # Update CSP img-src default directive
        $strReq = 'UPDATE ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            " SET setting_value = '" . $csp_prefix . "''self'' data: media.dotaddict.org blob:' " .
            " WHERE setting_id = 'csp_admin_img' " .
            " AND setting_ns = 'system' " .
            " AND setting_value = '''self'' data: media.dotaddict.org' ";
        App::con()->execute($strReq);

        # Update first publication on published posts
        $strReq = 'UPDATE ' . App::con()->prefix() . App::blog()::POST_TABLE_NAME .
            ' SET post_firstpub = 1' .
            ' WHERE post_status = ' . App::blog()::POST_PUBLISHED;
        App::con()->execute($strReq);

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'admin/csp_report.txt',
                'admin/js/jquery/jquery.modal.js',
                'admin/style/modal/close.png',
                'admin/style/modal/loader.gif',
                'admin/style/modal/modal.css',
                'admin/js/dragsort-tablerows.js',
                'admin/js/tool-man/cookies.js',
                'admin/js/tool-man/coordinates.js',
                'admin/js/tool-man/core.js',
                'admin/js/tool-man/css.js',
                'admin/js/tool-man/drag.js',
                'admin/js/tool-man/dragsort.js',
                'admin/js/tool-man/events.js',
                'admin/js/ie7/IE7.js',
                'admin/js/ie7/IE8.js',
                'admin/js/ie7/IE9.js',
                'admin/js/ie7/blank.gif',
                'admin/js/ie7/ie7-hashchange.js',
                'admin/js/ie7/ie7-recalc.js',
                'admin/js/ie7/ie7-squish.js',
                'admin/style/iesucks.css',
                'plugins/tags/js/jquery.autocomplete.js',
                'theme/ductile/ie.css',
            ],
            // Folders
            [
                'admin/style/modal',
                'admin/js/tool-man',
                'admin/js/ie7',
            ]
        );

        return $cleanup_sessions;
    }
}
