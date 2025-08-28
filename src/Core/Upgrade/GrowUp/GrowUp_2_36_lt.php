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
                'src/Config.php', // mv src/Core
                'src/Interface/ConfigInterface.php', // mv src/Interface/Core
                'src/Core/Connection.php', // rm
                'src/Interface/Config.php', // mv src/Interface/Core
                'src/Interface/Core/ConnectionInterface.php', // mv src/Interface/Database
                'src/Interface/Core/SchemaInterface.php', // mv src/Interface/Database
                'src/Interface/Database/InterfaceSchema.php', // merge in SchemaInterface
                'src/Database/InterfaceHandler.php', // merge in ConnectionInterface
                'src/Database/ContainerHandler.php', // rm
                'src/Database/ContainerSchema.php', // rm
                'src/Database/DbSchemaInterface.php', // rm
                'src/Database/DbHandlerInterface', // rm
            ],
            // Folders
            [
                'plugins/antispam/locales/lb',
                'src/Database/Driver', // mv src/Schema/Database
            ]
        );

        // credential database table was wrong in 2.36-dev-r20250820...
        $columns = App::db()->con()->schema()->getColumns(App::db()->con()->prefix() . 'credential');
        if (array_key_exists('credential_id', $columns)) {
            App::db()->con()->execute('DROP TABLE ' . App::db()->con()->prefix() . 'credential');
        }

        return $cleanup_sessions;
    }
}
