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

use Autoloader;
use dcCore;
use dcNsProcess;

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = true;

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        (new Autoloader(__NAMESPACE__ ))->addNamespace('Filters', __DIR__ . DIRECTORY_SEPARATOR . 'Filters');

        dcCore::app()->spamfilters = [
            __NAMESPACE__ . '\Filters\Ip',
            __NAMESPACE__ . '\Filters\IpLookup',
            __NAMESPACE__ . '\Filters\Words',
            __NAMESPACE__ . '\Filters\LinksLookup',
        ];

        // IP v6 filter depends on some math libraries, so enable it only if one of them is available
        if (function_exists('gmp_init') || function_exists('bcadd')) {
            dcCore::app()->spamfilters[] = __NAMESPACE__ . '\Filters\IpV6';
        }

        dcCore::app()->url->register('spamfeed', 'spamfeed', '^spamfeed/(.+)$', [FrontendUrl::class, 'spamFeed']);
        dcCore::app()->url->register('hamfeed', 'hamfeed', '^hamfeed/(.+)$', [FrontendUrl::class, 'hamFeed']);

        if (defined('DC_CONTEXT_ADMIN')) {
            // Register REST methods
            dcCore::app()->rest->addFunction('getSpamsCount', [Rest::class, 'getSpamsCount']);
        }

        return true;
    }
}
