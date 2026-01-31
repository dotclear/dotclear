<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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
     * @param   ArrayObject<string, array<array-key, class-string>>     $modules    The modules
     */
    public static function registerIeModules(ArrayObject $modules): void
    {
        $modules['import'][] = ModuleImportFlat::class;
        $modules['import'][] = ModuleImportFeed::class;

        $modules['export'][] = ModuleExportFlat::class;

        if (App::auth()->isSuperAdmin()) {
            $modules['import'][] = ModuleImportDc1::class;
            $modules['import'][] = ModuleImportWp::class;
        }
    }
}
