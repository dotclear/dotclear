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
                'src/Config.php',
                'src/Fault.php',
                'src/Core/Connection.php',
                'src/Core/Frontend/Session.php',
                'src/Core/Frontend/Url.php',
                'src/Core/Process.php',
                'src/Core/Utility.php',
                'src/Database/InterfaceHandler.php',
                'src/Database/InterfaceSchema.php',
                'src/Database/Session.php',
                'src/Exception/ExceptionEnum.php',
                'src/FileServer.php',
                'src/Interface/ConfigInterface.php',
                'src/Interface/Core/ConnectionInterface.php',
                'src/Interface/Core/SchemaInterface.php',

                // Dev only -- TO BE REMOVED BEFORE 2.36 RELEASE - WERE NOT IN 2.35
                'src/Interface/Database/DbSchemaInterface.php',
                'src/Interface/Database/DbHandlerInterface.php',
                'src/Interface/Database/InterfaceSchema.php',
                'src/Database/ContainerHandler.php',
                'src/Database/ContainerSchema.php',
                'src/Database/DbSchemaInterface.php',
                'src/Database/DbHandlerInterface.php',
            ],
            // Folders
            [
                'plugins/antispam/locales/lb',
                'src/Database/Driver',
                'src/Interface/Exception',

                // Dev only -- TO BE REMOVED BEFORE 2.36 RELEASE - WERE NOT IN 2.35
                'src/Schema/Database/Sqlite',
            ]
        );

        // Dev only -- TO BE REMOVED BEFORE 2.36 RELEASE - WERE NOT IN 2.35
        // credential database table was wrong in 2.36-dev-r20250820...
        $columns = App::db()->con()->schema()->getColumns(App::db()->con()->prefix() . 'credential');
        if (array_key_exists('credential_id', $columns)) {
            App::db()->con()->execute('DROP TABLE ' . App::db()->con()->prefix() . 'credential');
        }

        return $cleanup_sessions;
    }
}
