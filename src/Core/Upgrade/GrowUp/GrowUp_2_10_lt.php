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
use Dotclear\Helper\File\Files;

/**
 * @brief   Upgrade step.
 */
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
        @Files::makeDir(App::config()->varRoot());
        $f = App::config()->varRoot() . '/.htaccess';
        if (!file_exists($f)) {
            @file_put_contents($f, 'Require all denied' . "\n" . 'Deny from all' . "\n");
        }

        # Some new settings should be initialized, prepare db queries
        $sql = new InsertStatement();
        $sql
            ->into(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns(['setting_id', 'setting_ns', 'setting_value', 'setting_type', 'setting_label']);

        # Import feed control
        $sql
            ->values([['import_feed_url_control', 'system', (string) true, 'boolean', 'Control feed URL before import']], true)
            ->insert();
        $sql
            ->values([['import_feed_no_private_ip', 'system', (string) true, 'boolean', 'Prevent import feed from private IP']], true)
            ->insert();
        $sql
            ->values([['import_feed_ip_regexp', 'system', '', 'string', 'Authorize import feed only from this IP regexp']], true)
            ->insert();
        $sql
            ->values([['import_feed_port_regexp', 'system', '/^(80|443)$/', 'string', 'Authorize import feed only from this port regexp']], true)
            ->insert();

        # CSP directive (admin part)
        $sql
            ->values([['csp_admin_on', 'system', (string) true, 'boolean', 'Send CSP header (admin)']], true)
            ->insert();
        $sql
            ->values([['csp_admin_default', 'system', "'self'", 'string', 'CSP default-src directive']], true)
            ->insert();
        $sql
            ->values([['csp_admin_script', 'system', "'self' 'unsafe-inline' 'unsafe-eval'", 'string', 'CSP script-src directive']], true)
            ->insert();
        $sql
            ->values([['csp_admin_style', 'system', "'self' 'unsafe-inline'", 'string', 'CSP style-src directive']], true)
            ->insert();
        $sql
            ->values([['csp_admin_img', 'system', "'self' data: media.dotaddict.org", 'string', 'CSP img-src directive']], true)
            ->insert();

        return $cleanup_sessions;
    }
}
