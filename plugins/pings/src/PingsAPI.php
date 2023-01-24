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

use Exception;
use xmlrpcClient;

class PingsAPI extends xmlrpcClient
{
    /**
     * Does pings.
     *
     * @param      string       $srv_uri    The server uri
     * @param      null|string  $site_name  The site name
     * @param      null|string  $site_url   The site url
     *
     * @throws     Exception
     *
     * @return     bool
     */
    public static function doPings(string $srv_uri, ?string $site_name, ?string $site_url): bool
    {
        $xmlrpc_client          = new self($srv_uri);
        $xmlrpc_client->timeout = 3;

        $rsp = $xmlrpc_client->query('weblogUpdates.ping', $site_name, $site_url);

        if (isset($rsp['flerror']) && $rsp['flerror']) {
            throw new Exception($rsp['message']);
        }

        return true;
    }
}
