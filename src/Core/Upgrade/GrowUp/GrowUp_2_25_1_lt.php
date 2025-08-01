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

use Dotclear\Core\Upgrade\Upgrade;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_25_1_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                // Fix widgets plugin 2.25 house cleaning (see above)
                'plugins/widgets/_admin.php',
                'plugins/widgets/_init.php',
                'plugins/widgets/_install.php',
                'plugins/widgets/_public.php',
                'plugins/widgets/_prepend.php',
                'plugins/widgets/index.php',
                'plugins/widgets/style.css',
                'plugins/widgets/dragdrop.js',
                'plugins/widgets/widgets.js',
            ],
            // Folders
            [
                // default folder theme renamed to blowup
                'themes/default',
            ]
        );

        return $cleanup_sessions;
    }
}
