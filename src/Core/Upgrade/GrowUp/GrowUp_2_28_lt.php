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

use Dotclear\Core\Upgrade\Upgrade;

class GrowUp_2_28_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'src/Upgrade/dummy.txt',
                'inc/core/class.dc.error.php',
                'inc/core/class.dc.media.php',
                'inc/core/class.dc.meta.php',
                'inc/core/class.dc.notices.php',
                'inc/core/class.dc.postmedia.php',
                'inc/core/class.dc.rest.php',
                'inc/public/lib.urlhandlers.php',
                'inc/public/class.dc.template.php',
                'inc/public/lib.tpl.context.php',
            ],
            // Folders
            [
            ]
        );

        return $cleanup_sessions;
    }
}
