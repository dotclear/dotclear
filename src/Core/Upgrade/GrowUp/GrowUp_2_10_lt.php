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

use dcCore;
use dcNamespace;
use Dotclear\Core\Upgrade\Upgrade;
use Dotclear\Helper\File\Files;

class GrowUp_2_10_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'admin/js/jsUpload/vendor/jquery.ui.widget.js',
            ],
            // Folders
            [
                'admin/js/jsUpload/vendor',
            ]
        );

        # Create new var directory and its .htaccess file
        @Files::makeDir(DC_VAR);
        $f = DC_VAR . '/.htaccess';
        if (!file_exists($f)) {
            @file_put_contents($f, 'Require all denied' . "\n" . 'Deny from all' . "\n");
        }

        # Some new settings should be initialized, prepare db queries
        $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
            ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
            ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
        # Import feed control
        dcCore::app()->con->execute(
            sprintf($strReq, 'import_feed_url_control', (string) true, 'boolean', 'Control feed URL before import')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'import_feed_no_private_ip', (string) true, 'boolean', 'Prevent import feed from private IP')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'import_feed_ip_regexp', '', 'string', 'Authorize import feed only from this IP regexp')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'import_feed_port_regexp', '/^(80|443)$/', 'string', 'Authorize import feed only from this port regexp')
        );
        # CSP directive (admin part)
        dcCore::app()->con->execute(
            sprintf($strReq, 'csp_admin_on', (string) true, 'boolean', 'Send CSP header (admin)')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'csp_admin_default', "''self''", 'string', 'CSP default-src directive')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'csp_admin_script', "''self'' ''unsafe-inline'' ''unsafe-eval''", 'string', 'CSP script-src directive')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'csp_admin_style', "''self'' ''unsafe-inline''", 'string', 'CSP style-src directive')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'csp_admin_img', "''self'' data: media.dotaddict.org", 'string', 'CSP img-src directive')
        );

        return $cleanup_sessions;
    }
}
