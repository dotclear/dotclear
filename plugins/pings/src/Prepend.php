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

use dcBlog;
use dcCore;
use dcNsProcess;
use Exception;

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('coreFirstPublicationEntries', function (dcBlog $blog) {
            if (!$blog->settings->pings->pings_active) {
                return;
            }
            if (!$blog->settings->pings->pings_auto) {
                return;
            }

            $pings_uris = $blog->settings->pings->pings_uris;
            if (empty($pings_uris) || !is_array($pings_uris)) {
                return;
            }

            foreach ($pings_uris as $uri) {
                try {
                    PingsAPI::doPings($uri, $blog->name, $blog->url);
                } catch (Exception $e) {
                }
            }
        });

        return true;
    }
}
