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
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\InsertStatement;
use Dotclear\Database\Statement\SelectStatement;
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
            if (!is_dir($root)) {
                continue;
            }

            if (!is_readable($root)) {
                continue;
            }

            if (!str_ends_with($root, '/')) {
                $root .= '/';
            }

            if (@dir($root) === false) {
                continue;
            }

            if (@dir($root . 'daInstaller') === false) {
                continue;
            }

            Files::deltree($root . '/daInstaller');
        }

        # Some settings change, prepare db queries
        $table = App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME;

        # Add date and time formats
        $date_formats = ['%Y-%m-%d', '%m/%d/%Y', '%d/%m/%Y', '%Y/%m/%d', '%d.%m.%Y', '%b %e %Y', '%e %b %Y', '%Y %b %e',
            '%a, %Y-%m-%d', '%a, %m/%d/%Y', '%a, %d/%m/%Y', '%a, %Y/%m/%d', '%B %e, %Y', '%e %B, %Y', '%Y, %B %e', '%e. %B %Y',
            '%A, %B %e, %Y', '%A, %e %B, %Y', '%A, %Y, %B %e', '%A, %Y, %B %e', '%A, %e. %B %Y', ];
        $time_formats = ['%H:%M', '%I:%M', '%l:%M', '%Hh%M', '%Ih%M', '%lh%M'];
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $date_formats = array_map(fn (string $f): string => str_replace('%e', '%#d', $f), $date_formats);
        }

        $settings = [
            ['date_formats', serialize($date_formats), 'Date formats examples'],
            ['time_formats', serialize($time_formats), 'Time formats examples'],
            ['store_plugin_url', 'http://update.dotaddict.org/dc2/plugins.xml', 'Plugins XML feed location'],
            ['store_theme_url', 'http://update.dotaddict.org/dc2/themes.xml', 'Themes XML feed location'],
        ];
        foreach ($settings as [$setting_id, $setting_value, $setting_label]) {
            $sql = new SelectStatement();
            $rs  = $sql
                ->columns('count(1)')
                ->from($table)
                ->where('setting_id = ' . $sql->quote($setting_id))
                ->and('setting_ns = ' . $sql->quote('system'))
                ->and('blog_id IS NULL')
                ->select();
            if ($rs instanceof MetaRecord && $rs->cardinal() === 0) {
                $insert = new InsertStatement();
                $insert
                    ->into($table)
                    ->columns(['setting_id', 'setting_ns', 'setting_value', 'setting_type', 'setting_label'])
                    ->values([[$setting_id, 'system', $setting_value, 'string', $setting_label]])
                    ->insert();
            }
        }

        return $cleanup_sessions;
    }
}
