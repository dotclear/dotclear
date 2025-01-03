<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module prepend process.
 * @ingroup antispam
 */
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

        App::behavior()->addBehavior('AntispamInitFilters', function (ArrayObject $stack): string {
            $stack->append(Filters\Ip::class);
            $stack->append(Filters\IpLookup::class);
            $stack->append(Filters\Words::class);
            $stack->append(Filters\LinksLookup::class);

            return '';
        });

        // IP v6 filter depends on some math libraries, so enable it only if one of them is available
        if (function_exists('gmp_init') || function_exists('bcadd')) {
            App::behavior()->addBehavior('AntispamInitFilters', function (ArrayObject $stack): string {
                $stack->append(Filters\IpV6::class);

                return '';
            });
        }

        App::url()->register('spamfeed', 'spamfeed', '^spamfeed/(.+)$', FrontendUrl::spamFeed(...));
        App::url()->register('hamfeed', 'hamfeed', '^hamfeed/(.+)$', FrontendUrl::hamFeed(...));

        if (App::task()->checkContext('BACKEND')) {
            // Register REST methods
            App::rest()->addFunction('getSpamsCount', Rest::getSpamsCount(...));
        }

        return true;
    }
}
