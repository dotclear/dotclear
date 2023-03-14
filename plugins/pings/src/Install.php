<?php
/**
 * @brief pings, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pings;

use dcCore;
use dcNsProcess;

class Install extends dcNsProcess
{
    private static array $default_pings_uris = [
        'Ping-o-Matic!' => 'http://rpc.pingomatic.com/',
    ];

    public static function init(): bool
    {
        $module     = basename(dirname(__DIR__));
        self::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion($module, dcCore::app()->plugins->moduleInfo($module, 'version'));

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        $s = dcCore::app()->blog->settings->get('pings');
        $s->put('pings_active', 1, 'boolean', 'Activate pings plugin', false, true);
        $s->put('pings_auto', 0, 'boolean', 'Auto pings on 1st publication', false, true);
        $s->put('pings_uris', self::$default_pings_uris, 'array', 'Pings services URIs', false, true);

        return true;
    }
}
