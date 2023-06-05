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

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        if (!defined('DC_FAIRTRACKBACKS_FORCE')) {
            define('DC_FAIRTRACKBACKS_FORCE', false);
        }

        return (static::$init = My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (!DC_FAIRTRACKBACKS_FORCE) {
            dcCore::app()->spamfilters[] = AntispamFilterFairTrackbacks::class;
        }

        return true;
    }
}
