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
class GrowUp_2_31_lt
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
                    'admin/style/scss',
                    'themes/berlin/scss',
                ]
            );
        }

        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
            ],
            // Folders
            [
            ]
        );

        return $cleanup_sessions;
    }
}
