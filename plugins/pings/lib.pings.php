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

if (!defined('DC_RC_PATH')) {return;}

class pingsAPI extends xmlrpcClient
{
    public static function doPings($srv_uri, $site_name, $site_url)
    {
        $o          = new self($srv_uri);
        $o->timeout = 3;

        $rsp = $o->query('weblogUpdates.ping', $site_name, $site_url);

        if (isset($rsp['flerror']) && $rsp['flerror']) {
            throw new Exception($rsp['message']);
        }

        return true;
    }
}

class pingsAdminBehaviors
{
    public static function pingJS()
    {
        global $core;

        $res =
        "<script type=\"text/javascript\">\n" .
        dcPage::jsVar('dotclear.msg.check_all', __('Check all')) . "\n" .
        "</script>\n" .
        dcPage::jsLoad(dcPage::getPF('pings/js/post.js'));

        return $res;
    }

    public static function pingsFormItems($main, $sidebar, $post)
    {
        $core = &$GLOBALS['core'];
        if (!$core->blog->settings->pings->pings_active) {
            return;
        }

        $pings_uris = $core->blog->settings->pings->pings_uris;
        if (empty($pings_uris) || !is_array($pings_uris)) {
            return;
        }

        if (!empty($_POST['pings_do']) && is_array($_POST['pings_do'])) {
            $pings_do = $_POST['pings_do'];
        } else {
            $pings_do = array();
        }

        $item = '<h5 class="ping-services">' . __('Pings') . '</h5>';
        $i    = 0;
        foreach ($pings_uris as $k => $v) {
            $item .=
            '<p class="ping-services"><label for="pings_do-' . $i . '" class="classic">' .
            form::checkbox(array('pings_do[]', 'pings_do-' . $i), html::escapeHTML($v), in_array($v, $pings_do), 'check-ping-services') . ' ' .
            html::escapeHTML($k) . '</label></p>';
            $i++;
        }
        $sidebar['options-box']['items']['pings'] = $item;

    }

    public static function doPings($cur, $post_id)
    {
        if (empty($_POST['pings_do']) || !is_array($_POST['pings_do'])) {
            return;
        }

        $core = &$GLOBALS['core'];
        if (!$core->blog->settings->pings->pings_active) {
            return;
        }

        $pings_uris = $core->blog->settings->pings->pings_uris;
        if (empty($pings_uris) || !is_array($pings_uris)) {
            return;
        }

        foreach ($_POST['pings_do'] as $uri) {
            if (in_array($uri, $pings_uris)) {
                try {
                    pingsAPI::doPings($uri, $core->blog->name, $core->blog->url);
                } catch (Exception $e) {}
            }
        }
    }
}

class pingsCoreBehaviour
{
    public static function doPings($blog, $ids)
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
            } catch (Exception $e) {}
        }
    }
}
