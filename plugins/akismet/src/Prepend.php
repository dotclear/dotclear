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
use Dotclear\Core\Core;
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Akismet') . __('Akismet interface for Dotclear');

        return self::status(defined('DC_RC_PATH'));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Core::behavior()->addBehavior('AntispamInitFilters', function ($stack) {
            $stack->append(AntispamFilterAkismet::class);
        });

        return true;
    }
}
