<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use Dotclear\Core\Core;
use Dotclear\Core\Process;

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

        Core::behavior()->addBehavior('initWidgets', Widgets::initWidgets(...));

        // Simple menu template functions
        Core::frontend()->tpl->addValue('SimpleMenu', FrontendTemplate::simpleMenu(...));

        return true;
    }
}
