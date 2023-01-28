<?php
/**
 * @brief fairTrackbacks, an antispam filter plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\fairTrackbacks;

use dcCore;
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = true;

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        if (DC_FAIRTRACKBACKS_FORCE) {
            dcCore::app()->spamfilters[] = __NAMESPACE__ . '\AntispamFilterFairTrackbacks';
        }

        return true;
    }
}
