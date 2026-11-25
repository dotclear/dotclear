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
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_41_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                'inc/core/class.dc.core.php',
                'inc/core/class.dc.utils.php',
                // Remove previously installed DotclearWatch plugin in standard plugins folder
                'plugins/DotclearWatch/CHANGELOG.md',
                'plugins/DotclearWatch/dcstore.xml',
                'plugins/DotclearWatch/LICENSE',
                'plugins/DotclearWatch/README.md',
            ],
            // Folders
            [
                // Remove previously installed DotclearWatch plugin in standard plugins folder
                'plugins/DotclearWatch/locales',
            ]
        );

        // Remove DotclearWatch plugins if installed outside standard plugins folder
        $distributed_root = Path::real(App::config()->dotclearRoot()) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
        $paths            = explode(PATH_SEPARATOR, App::config()->pluginsRoot());
        foreach ($paths as $path) {
            $root = rtrim(Path::real($path, false), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ($root !== $distributed_root) {
                $module = $root . DIRECTORY_SEPARATOR . 'DotclearWatch' . DIRECTORY_SEPARATOR;
                if (is_dir($module)) {
                    Files::deltree($module);
                }
            }
        }

        return $cleanup_sessions;
    }
}
