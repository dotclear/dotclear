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
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (DC_FAIRTRACKBACKS_FORCE) {
            dcCore::app()->spamfilters[] = AntispamFilterFairTrackbacks::class;
        }

        return true;
    }
}
