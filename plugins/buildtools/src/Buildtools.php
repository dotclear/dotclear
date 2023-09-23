<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\buildtools;

use Dotclear\Plugin\maintenance\Maintenance;

/**
 * @brief   The module maintenance task caller.
 * @ingroup buildtools
 */
class Buildtools
{
    /**
     * Add l10n faker maintenance task.
     *
     * @param   Maintenance     $maintenance    The maintenance object
     */
    public static function maintenanceAdmin(Maintenance $maintenance): void
    {
        $maintenance->addTask(BuildtoolsMaintenanceTask::class);
    }
}
