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
class GrowUp_2_35_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'admin/images/trash.png',
                'admin/style/loader.gif',
                'admin/style/scss/themes/_dark.scss',
                'admin/style/scss/themes/_default.scss',
                'admin/style/scss/themes/_themes.scss',
                'src/Exception/UnautorizedException.php',   // Already in 2.33 but with a typo :-p
                'themes/ductile/ductile.js',
            ],
            // Folders
            [
                'cache/cbfeed',
            ]
        );

        return $cleanup_sessions;
    }
}
