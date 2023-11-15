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
class GrowUp_2_28_1_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                // typo or missing in some previous housecleanings
                'admin/images/close.svg',
                'admin/images/pagination/first.png',
                'admin/images/pagination/last.png',
                'admin/images/pagination/next.png',
                'admin/images/pagination/no-first.png',
                'admin/images/pagination/no-last.png',
                'admin/images/pagination/no-next.png',
                'admin/images/pagination/no-previous.png',
                'admin/images/pagination/previous.png',
                'plugins/tags/style.css',
                'src/Helper/Deprecated.php',
            ],
            // Folders
            [
            ]
        );

        return $cleanup_sessions;
    }
}
