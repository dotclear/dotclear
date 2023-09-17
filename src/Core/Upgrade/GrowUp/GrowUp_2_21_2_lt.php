<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\Core\Upgrade\Upgrade;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_21_2_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            null,
            // Folders
            [
                'inc/public/default-templates/currywurst',
                'plugins/pages/default-templates/currywurst',
                'plugins/tags/default-templates/currywurst',
            ]
        );

        return $cleanup_sessions;
    }
}
