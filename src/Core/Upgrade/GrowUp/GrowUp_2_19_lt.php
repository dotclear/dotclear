<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;
use Dotclear\Core\Upgrade\Upgrade;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_19_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                // No more used in Berlin theme
                'themes/berlin/scripts/boxsizing.htc',
                // That old easter egg is not more present
                'admin/images/thanks.mp3',
                // No more used jQuery pwd strength and cookie plugins
                'admin/js/jquery/jquery.pwstrength.js',
                'admin/js/jquery/jquery.biscuit.js',
                // No more need of this fake common.js (was used by install)
                'admin/js/mini-common.js',
            ],
            // Folders
            [
                // Oldest jQuery public lib
                'inc/js/jquery/3.5.1',
                // No more used in Berlin theme
                'themes/berlin/scripts',
            ]
        );

        # Global settings
        $strReq = 'INSERT INTO ' . App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME .
            ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
            ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
        App::con()->execute(
            sprintf($strReq, 'prevents_clickjacking', (string) true, 'boolean', 'Prevents Clickjacking')
        );
        App::con()->execute(
            sprintf($strReq, 'prevents_floc', (string) true, 'boolean', 'Prevents FLoC tracking')
        );

        return $cleanup_sessions;
    }
}
