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

use Dotclear\App;
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

        App::behavior()->addBehavior('AntispamInitFilters', function ($stack) {
            $stack->append(Filters\Ip::class);
            $stack->append(Filters\IpLookup::class);
            $stack->append(Filters\Words::class);
            $stack->append(Filters\LinksLookup::class);
        });

        // IP v6 filter depends on some math libraries, so enable it only if one of them is available
        if (function_exists('gmp_init') || function_exists('bcadd')) {
            App::behavior()->addBehavior('AntispamInitFilters', function ($stack) {
                $stack->append(Filters\IpV6::class);
            });
        }

        App::url()->register('spamfeed', 'spamfeed', '^spamfeed/(.+)$', FrontendUrl::spamFeed(...));
        App::url()->register('hamfeed', 'hamfeed', '^hamfeed/(.+)$', FrontendUrl::hamFeed(...));

        if (App::context('BACKEND')) {
            // Register REST methods
            App::rest()->addFunction('getSpamsCount', Rest::getSpamsCount(...));
        }

        return true;
    }
}
