<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use dcCore;
use Dotclear\Core\Core;
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->spamfilters = [
            Filters\Ip::class,
            Filters\IpLookup::class,
            Filters\Words::class,
            Filters\LinksLookup::class,
        ];

        // IP v6 filter depends on some math libraries, so enable it only if one of them is available
        if (function_exists('gmp_init') || function_exists('bcadd')) {
            dcCore::app()->spamfilters[] = Filters\IpV6::class;
        }

        Core::url()->register('spamfeed', 'spamfeed', '^spamfeed/(.+)$', [FrontendUrl::class, 'spamFeed']);
        Core::url()->register('hamfeed', 'hamfeed', '^hamfeed/(.+)$', [FrontendUrl::class, 'hamFeed']);

        if (defined('DC_CONTEXT_ADMIN')) {
            // Register REST methods
            Core::rest()->addFunction('getSpamsCount', [Rest::class, 'getSpamsCount']);
        }

        return true;
    }
}
