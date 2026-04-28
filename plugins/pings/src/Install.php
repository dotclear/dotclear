<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pings;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   The module install process.
 * @ingroup pings
 */
class Install
{
    use TraitProcess;

    /**
     * Default ping URIs.
     *
     * @var     array<string,string>    $default_pings_uris
     */
    private static array $default_pings_uris = [
        'Ping-o-Matic!' => 'http://rpc.pingomatic.com/',
    ];

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::settings()->put('pings_active', 1, App::blogWorkspace()::NS_BOOL, 'Activate pings plugin', false, true);
        My::settings()->put('pings_auto', 0, App::blogWorkspace()::NS_BOOL, 'Auto pings on 1st publication', false, true);
        My::settings()->put('pings_uris', self::$default_pings_uris, App::blogWorkspace()::NS_ARRAY, 'Pings services URIs', false, true);

        return true;
    }
}
