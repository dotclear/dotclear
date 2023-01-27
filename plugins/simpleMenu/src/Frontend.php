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
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_RC_PATH')) {
            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('initWidgets', [Widgets::class, 'initWidgets']);
        dcCore::app()->addBehavior('widgetGetCallback', function ($widget) {
            if ($widget['id'] === 'simplemenu') {
                $widget['callback'] = [FrontendTemplate::class, 'simpleMenuWidget'];
            }
        });

        // Simple menu template functions
        dcCore::app()->tpl->addValue('SimpleMenu', [FrontendTemplate::class, 'simpleMenu']);

        return true;
    }
}
