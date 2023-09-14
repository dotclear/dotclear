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

use Dotclear\App;
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Akismet') . __('Akismet interface for Dotclear');

        return self::status(App::config()->configPath() != '');
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('AntispamInitFilters', function ($stack) {
            $stack->append(AntispamFilterAkismet::class);
        });

        return true;
    }
}
