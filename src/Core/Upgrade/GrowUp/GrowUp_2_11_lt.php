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
class GrowUp_2_11_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Some new settings should be initialized, prepare db queries
        $sql = new InsertStatement();
        $sql
            ->into(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns(['setting_id', 'setting_ns', 'setting_value', 'setting_type', 'setting_label'])
            ->values([['csp_admin_report_only', 'system', (string) false, 'boolean', 'CSP Report only violations (admin)']])
            ->insert();

        // SQlite driver does not allow using single quote at beginning or end of a field value
        // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
        $csp_prefix = App::db()->con()->syntax() === 'sqlite' ? 'localhost ' : ''; // Hack for SQlite driver
        $csp_suffix = App::db()->con()->syntax() === 'sqlite' ? ' 127.0.0.1' : ''; // Hack for SQlite driver
        $table      = App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME;

        $update = function (string $setting_id, string $value, string $previous) use ($table): void {
            $sql = new UpdateStatement();
            $sql
                ->ref($table)
                ->columns('setting_value')
                ->values($value)
                ->where('setting_id = ' . $sql->quote($setting_id))
                ->and('setting_ns = ' . $sql->quote('system'))
                ->and('setting_value = ' . $sql->quote($previous))
                ->update();
        };

        # Try to fix some CSP directive wrongly stored for SQLite drivers
        $update('csp_admin_default', $csp_prefix . "'self'" . $csp_suffix, 'self');
        $update('csp_admin_script', $csp_prefix . "'self' 'unsafe-inline' 'unsafe-eval'" . $csp_suffix, "self' 'unsafe-inline' 'unsafe-eval");
        $update('csp_admin_style', $csp_prefix . "'self' 'unsafe-inline'" . $csp_suffix, "self' 'unsafe-inline");
        $update('csp_admin_img', $csp_prefix . "'self' data: media.dotaddict.org blob:", "self' data: media.dotaddict.org");

        # Update CSP img-src default directive
        $update('csp_admin_img', $csp_prefix . "'self' data: media.dotaddict.org blob:", "'self' data: media.dotaddict.org");

        # Update first publication on published posts
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME)
            ->set('post_firstpub = 1')
            ->where('post_status = ' . App::status()->post()::PUBLISHED)
            ->update();

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
