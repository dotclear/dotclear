<?php
/**
 * @brief akismet, an antispam filter plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\akismet;

use dcCore;
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        static::$init = defined('DC_RC_PATH');

        // Dead but useful code (for l10n)
        __('Akismet') . __('Akismet interface for Dotclear');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->spamfilters[] = AntispamFilterAkismet::class;

        return true;
    }
}
