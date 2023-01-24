<?php
/**
 * @brief buildtools, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\buildtools;

use Dotclear\Plugin\maintenance\Maintenance;

class Buildtools
{
    /**
     * Add l10n faker maintenance task
     *
     * @param      Maintenance  $maintenance  The maintenance object
     */
    public static function maintenanceAdmin(Maintenance $maintenance)
    {
        $maintenance->addTask(BuildtoolsMaintenanceTask::class);
    }
}
