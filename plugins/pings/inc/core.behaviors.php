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
if (!defined('DC_RC_PATH')) {
    return;
}

class pingsCoreBehaviour
{
    /**
     * Does pings.
     *
     * @param      dcBlog  $blog   The blog
     */
    public static function doPings(dcBlog $blog)
    {
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
                pingsAPI::doPings($uri, $blog->name, $blog->url);
            } catch (Exception $e) {
            }
        }
    }
}
