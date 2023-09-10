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

use Dotclear\App;
use Dotclear\Core\Upgrade\Upgrade;

class GrowUp_2_17_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'inc/admin/class.dc.notices.php',
            ],
            // Folders
            [
                // Oldest jQuery public lib
                'inc/js/jquery/3.4.1',
            ]
        );

        // Help specific (files was moved)
        $remtree  = scandir(App::config()->dotclearRoot() . '/locales');
        $remfiles = [
            'help/blowupConfig.html',
            'help/themeEditor.html',
        ];
        foreach ($remtree as $dir) {
            if (is_dir(App::config()->dotclearRoot() . '/' . 'locales' . '/' . $dir) && $dir !== '.' && $dir !== '.') {
                foreach ($remfiles as $f) {
                    @unlink(App::config()->dotclearRoot() . '/' . 'locales' . '/' . $dir . '/' . $f);
                }
            }
        }

        return $cleanup_sessions;
    }
}
