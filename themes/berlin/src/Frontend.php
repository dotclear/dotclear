<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Theme\berlin;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module frontend process.
 * @ingroup berlin
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('publicHeadContent', function (): string {
            echo
            Html::jsJson('dotclear_berlin', [
                'show_menu'  => __('Show menu'),
                'hide_menu'  => __('Hide menu'),
                'navigation' => __('Main menu'),
            ]);

            return '';
        });

        return true;
    }
}
