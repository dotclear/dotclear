<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pings;

use Dotclear\Core\Process;

/**
 * @brief   The module install process.
 * @ingroup pings
 */
class Install extends Process
{
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

        My::settings()->put('pings_active', 1, 'boolean', 'Activate pings plugin', false, true);
        My::settings()->put('pings_auto', 0, 'boolean', 'Auto pings on 1st publication', false, true);
        My::settings()->put('pings_uris', self::$default_pings_uris, 'array', 'Pings services URIs', false, true);

        return true;
    }
}
