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
class GrowUp_2_16_1_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                // Oldest jQuery public lib
                'inc/js/jquery/1.4.2/jquery.js',
                'inc/js/jquery/1.4.2/jquery.cookie.js',
                'inc/js/jquery/1.11.1/jquery.js',
                'inc/js/jquery/1.11.1/jquery.cookie.js',
                'inc/js/jquery/1.11.3/jquery.js',
                'inc/js/jquery/1.11.3/jquery.cookie.js',
                'inc/js/jquery/1.12.4/jquery.js',
                'inc/js/jquery/1.12.4/jquery.cookie.js',
                'inc/js/jquery/2.2.0/jquery.js',
                'inc/js/jquery/2.2.0/jquery.cookie.js',
                'inc/js/jquery/2.2.4/jquery.js',
                'inc/js/jquery/2.2.4/jquery.cookie.js',
                'inc/js/jquery/3.3.1/jquery.js',
                'inc/js/jquery/3.3.1/jquery.cookie.js',
            ],
            // Folders
            [
                // Oldest jQuery public lib
                'inc/js/jquery/1.4.2',
                'inc/js/jquery/1.11.1',
                'inc/js/jquery/1.11.3',
                'inc/js/jquery/1.12.4',
                'inc/js/jquery/2.2.0',
                'inc/js/jquery/2.2.4',
                'inc/js/jquery/3.3.1',
            ]
        );

        return $cleanup_sessions;
    }
}
