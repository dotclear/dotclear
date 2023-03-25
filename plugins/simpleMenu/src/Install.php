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
use Dotclear\Helper\Html\Html;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        $module       = basename(dirname(__DIR__));
        static::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion($module, dcCore::app()->plugins->moduleInfo($module, 'version'));

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        # Menu par défaut
        $blog_url     = Html::stripHostURL(dcCore::app()->blog->url);
        $menu_default = [
            ['label' => 'Home', 'descr' => 'Recent posts', 'url' => $blog_url, 'targetBlank' => false],
            ['label' => 'Archives', 'descr' => '', 'url' => $blog_url . dcCore::app()->url->getURLFor('archive'), 'targetBlank' => false],
        ];

        dcCore::app()->blog->settings->system->put('simpleMenu', $menu_default, 'array', 'simpleMenu default menu', false, true);
        dcCore::app()->blog->settings->system->put('simpleMenu_active', true, 'boolean', 'Active', false, true);

        return true;
    }
}
