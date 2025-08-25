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

use Dotclear\App;
use Dotclear\Core\Upgrade\Upgrade;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_36_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'locales/ar/core_core_*.html',
                'locales/el/core_core_*.html',
                'locales/he/core_core_*.html',
                'locales/uk/core_core_*.html',
                'src/Config.php', //moved to src/Core
                'src/Interface/Config.php', // moved to src/Interface/Core
            ],
            // Folders
            [
                'plugins/antispam/locales/lb',
            ]
        );

        // credential database table was wrong in 2.36-dev-r20250820...
        $columns = App::con()->schema()->getColumns(App::con()->prefix() . 'credential');
        if (array_key_exists('credential_id', $columns)) {
            App::con()->execute('DROP TABLE ' . App::con()->prefix() . 'credential');
        }

        return $cleanup_sessions;
    }
}
