<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use ArrayObject;
use Dotclear\App;

/**
 * @brief   The module backend behaviors.
 * @ingroup importExport
 */
class BackendBehaviors
{
    /**
     * Register import/export modules.
     *
     * @param   ArrayObject<string, array<mixed>>     $modules    The modules
     */
    public static function registerIeModules(ArrayObject $modules): void
    {
        $compose = fn ($src, $add) => isset($src) ? array_merge($src, $add) : $add;

        $modules['import'] = $compose($modules['import'], [ModuleImportFlat::class]);
        $modules['import'] = $compose($modules['import'], [ModuleImportFeed::class]);

        $modules['export'] = $compose($modules['export'], [ModuleExportFlat::class]);

        if (App::auth()->isSuperAdmin()) {
            $modules['import'] = $compose($modules['import'], [ModuleImportDc1::class]);
            $modules['import'] = $compose($modules['import'], [ModuleImportWp::class]);
        }
    }
}
