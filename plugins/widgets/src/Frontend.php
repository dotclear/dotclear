<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use dcCore;
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        Widgets::init();

        dcCore::app()->tpl->addValue('Widgets', [FrontendTemplate::class, 'tplWidgets']);
        dcCore::app()->tpl->addBlock('Widget', [FrontendTemplate::class, 'tplWidget']);
        dcCore::app()->tpl->addBlock('IfWidgets', [FrontendTemplate::class, 'tplIfWidgets']);

        return true;
    }
}
