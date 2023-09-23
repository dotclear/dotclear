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
        $modules['import'] = array_merge($modules['import'], [ModuleImportFlat::class]);
        $modules['import'] = array_merge($modules['import'], [ModuleImportFeed::class]);

        $modules['export'] = array_merge($modules['export'], [ModuleExportFlat::class]);

        if (App::auth()->isSuperAdmin()) {
            $modules['import'] = array_merge($modules['import'], [ModuleImportDc1::class]);
            $modules['import'] = array_merge($modules['import'], [ModuleImportWp::class]);
        }
    }
}
