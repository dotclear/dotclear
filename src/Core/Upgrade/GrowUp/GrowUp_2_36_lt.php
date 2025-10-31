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
 *
 * @todo switch to SqlStatement
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
                'src/PHPGloblal.php',
                'src/Core/Connection.php',
                'src/Core/Frontend/Session.php',
                'src/Core/Frontend/Url.php',
                'src/Core/Process.php',
                'src/Core/Utility.php',
                'src/Database/InterfaceHandler.php',
                'src/Database/InterfaceSchema.php',
                'src/Database/Session.php',
                'src/Exception/AbstractException.php',
                'src/Exception/ExceptionEnum.php',
                'src/FileServer.php',
                'src/Helper/L10nGlobal.php',
                'src/Interface/ConfigInterface.php',
                'src/Interface/Core/ConnectionInterface.php',
                'src/Interface/Core/SchemaInterface.php',
            ],
            // Folders
            [
                'plugins/antispam/locales/lb',
                'src/Database/Driver',
                'src/Interface/Exception',
            ]
        );

        return $cleanup_sessions;
    }
}
