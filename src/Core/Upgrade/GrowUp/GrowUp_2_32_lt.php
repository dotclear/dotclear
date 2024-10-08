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

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_32_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        if (!str_contains(App::config()->dotclearVersion(), 'dev')) {
            // A bit of housecleaning for no longer needed folders, but only if not in dev mode
            // Keeping sources to build production files
            Upgrade::houseCleaning(
                // Files
                [
                ],
                // Folders
                [
                ]
            );
        }

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'src/Process/Backend/Update.php',
                'src/Helper/tz.dat',
            ],
            // Folders
            [
            ]
        );

        return $cleanup_sessions;
    }
}
