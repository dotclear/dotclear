<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Theme\customCSS;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   The module prepend process.
 * @ingroup antispam
 */
class Prepend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Set tplset according to settings
        $theme = App::blog()->settings()->get('system')->getStr('theme', false);
        if ($theme === My::id()) {
            $tplset = App::blog()->settings()->get('themes')->getStr($theme . '_tplset', false);
            if ($tplset !== '') {
                App::themes()->getDefine($theme)->set('tplset', $tplset);
            }
        }

        return true;
    }
}
