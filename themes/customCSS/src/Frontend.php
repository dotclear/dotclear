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
 * @brief   The module frontend process.
 * @ingroup customCSS
 */
class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (self::status()) {
            App::behavior()->addBehavior('publicHeadContent', function (): void {
                if (App::blog()->settings()->get('system')->getStr('theme', false) === My::id()) {
                    $p_url = App::blog()->settings()->get('system')->getStr('public_url', false);

                    echo
                    '<link rel="stylesheet" type="text/css" href="' . $p_url . DIRECTORY_SEPARATOR . My::id() . '.css' . '" media="screen">' . "\n";
                }
            });
        }

        return self::status();
    }
}
