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

        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'initWidgets']);

        // Simple menu template functions
        dcCore::app()->tpl->addValue('SimpleMenu', [FrontendTemplate::class, 'simpleMenu']);

        return true;
    }
}
