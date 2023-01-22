<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

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

        dcCore::app()->tpl->addValue('Blogroll', [FrontendTemplate::class, 'blogroll']);
        dcCore::app()->tpl->addValue('BlogrollXbelLink', [FrontendTemplate::class, 'blogrollXbelLink']);

        dcCore::app()->url->register('xbel', 'xbel', '^xbel(?:\/?)$', [FrontendUrl::class, 'xbel']);

        dcCore::app()->addBehaviors([
            'initWidgets'        => [Widgets::class, 'initWidgets'],
            'initDefaultWidgets' => [Widgets::class, 'initDefaultWidgets'],
        ]);

        return true;
    }
}
