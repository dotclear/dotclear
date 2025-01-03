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
use Dotclear\Core\Process;
use Dotclear\Interface\Core\BlogInterface;
use Exception;

/**
 * @brief   The module prepend process.
 * @ingroup pings
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

        App::behavior()->addBehavior('coreFirstPublicationEntries', function (BlogInterface $blog): string {
            if (!$blog->settings()->pings->pings_active) {
                return '';
            }
            if (!$blog->settings()->pings->pings_auto) {
                return'';
            }

            $pings_uris = $blog->settings()->pings->pings_uris;
            if (empty($pings_uris) || !is_array($pings_uris)) {
                return'';
            }

            foreach ($pings_uris as $uri) {
                try {
                    PingsAPI::doPings($uri, $blog->name(), $blog->url());
                } catch (Exception) {
                }
            }

            return '';
        });

        return true;
    }
}
