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

use dcCore;
use Dotclear\Core\Core;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(defined('DC_CONTEXT_ADMIN'));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Core::behavior()->addBehavior('dcMaintenanceInit', [Buildtools::class, 'maintenanceAdmin']);

        return true;
    }
}
