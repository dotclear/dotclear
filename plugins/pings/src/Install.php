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
use Dotclear\Core\Process;

class Install extends Process
{
    private static array $default_pings_uris = [
        'Ping-o-Matic!' => 'http://rpc.pingomatic.com/',
    ];

    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        $s = dcCore::app()->blog->settings->get('pings');
        $s->put('pings_active', 1, 'boolean', 'Activate pings plugin', false, true);
        $s->put('pings_auto', 0, 'boolean', 'Auto pings on 1st publication', false, true);
        $s->put('pings_uris', self::$default_pings_uris, 'array', 'Pings services URIs', false, true);

        return true;
    }
}
