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
        $theme = is_string($theme = App::blog()->settings()->system->theme) ? $theme : '';
        if ($theme === My::id()) {
            $tplset = is_string($tplset = App::blog()->settings()->themes->get($theme . '_tplset')) ? $tplset : '';
            if ($tplset !== '') {
                App::themes()->getDefine($theme)->set('tplset', $tplset);
            }
        }

        return true;
    }
}
