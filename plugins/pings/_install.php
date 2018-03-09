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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$version = $core->plugins->moduleInfo('pings', 'version');
if (version_compare($core->getVersion('pings'), $version, '>=')) {
    return;
}

// Default pings services
$default_pings_uris = array(
    'Ping-o-Matic!'      => 'http://rpc.pingomatic.com/',
    'Google Blog Search' => 'http://blogsearch.google.com/ping/RPC2'
);

$core->blog->settings->addNamespace('pings');
$core->blog->settings->pings->put('pings_active', 1, 'boolean', 'Activate pings plugin', false, true);
$core->blog->settings->pings->put('pings_auto', 0, 'boolean', 'Auto pings on 1st publication', false, true);
$core->blog->settings->pings->put('pings_uris', $default_pings_uris, 'array', 'Pings services URIs', false, true);

$core->setVersion('pings', $version);
return true;
