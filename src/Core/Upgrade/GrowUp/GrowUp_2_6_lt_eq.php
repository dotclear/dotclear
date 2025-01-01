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
use Dotclear\Helper\File\Files;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_6_lt_eq
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'README',
                'admin/trackbacks.php',
            ],
        );

        # daInstaller has been integrated to the core.
        # Try to remove it
        $path = explode(PATH_SEPARATOR, App::config()->pluginsRoot());
        foreach ($path as $root) {
            if (!is_dir($root) || !is_readable($root)) {
                continue;
            }
            if (!str_ends_with($root, '/')) {
                $root .= '/';
            }
            if (($p = @dir($root)) === false) {
                continue;
            }
            if (($d = @dir($root . 'daInstaller')) === false) {
                continue;
            }
            Files::deltree($root . '/daInstaller');
        }

        # Some settings change, prepare db queries
        $strReqFormat = 'INSERT INTO ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME;
        $strReqFormat .= ' (setting_id,setting_ns,setting_value,setting_type,setting_label)';
        $strReqFormat .= ' VALUES(\'%s\',\'system\',\'%s\',\'string\',\'%s\')';

        $strReqSelect = 'SELECT count(1) FROM ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME;
        $strReqSelect .= ' WHERE setting_id = \'%s\'';
        $strReqSelect .= ' AND setting_ns = \'system\'';
        $strReqSelect .= ' AND blog_id IS NULL';

        # Add date and time formats
        $date_formats = ['%Y-%m-%d', '%m/%d/%Y', '%d/%m/%Y', '%Y/%m/%d', '%d.%m.%Y', '%b %e %Y', '%e %b %Y', '%Y %b %e',
            '%a, %Y-%m-%d', '%a, %m/%d/%Y', '%a, %d/%m/%Y', '%a, %Y/%m/%d', '%B %e, %Y', '%e %B, %Y', '%Y, %B %e', '%e. %B %Y',
            '%A, %B %e, %Y', '%A, %e %B, %Y', '%A, %Y, %B %e', '%A, %Y, %B %e', '%A, %e. %B %Y', ];
        $time_formats = ['%H:%M', '%I:%M', '%l:%M', '%Hh%M', '%Ih%M', '%lh%M'];
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $date_formats = array_map(fn ($f): string => str_replace('%e', '%#d', $f), $date_formats);
        }

        $rs = App::con()->select(sprintf($strReqSelect, 'date_formats'));
        if ($rs->f(0) == 0) {
            $strReq = sprintf($strReqFormat, 'date_formats', serialize($date_formats), 'Date formats examples');
            App::con()->execute($strReq);
        }
        $rs = App::con()->select(sprintf($strReqSelect, 'time_formats'));
        if ($rs->f(0) == 0) {
            $strReq = sprintf($strReqFormat, 'time_formats', serialize($time_formats), 'Time formats examples');
            App::con()->execute($strReq);
        }

        # Add repository URL for themes and plugins as daInstaller move to core
        $rs = App::con()->select(sprintf($strReqSelect, 'store_plugin_url'));
        if ($rs->f(0) == 0) {
            $strReq = sprintf($strReqFormat, 'store_plugin_url', 'http://update.dotaddict.org/dc2/plugins.xml', 'Plugins XML feed location');
            App::con()->execute($strReq);
        }
        $rs = App::con()->select(sprintf($strReqSelect, 'store_theme_url'));
        if ($rs->f(0) == 0) {
            $strReq = sprintf($strReqFormat, 'store_theme_url', 'http://update.dotaddict.org/dc2/themes.xml', 'Themes XML feed location');
            App::con()->execute($strReq);
        }

        return $cleanup_sessions;
    }
}
