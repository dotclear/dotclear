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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$version = dcCore::app()->plugins->moduleInfo('pings', 'version');
if (version_compare(dcCore::app()->getVersion('pings'), $version, '>=')) {
    return;
}

// Default pings services
$default_pings_uris = [
    'Ping-o-Matic!' => 'http://rpc.pingomatic.com/',
];

dcCore::app()->blog->settings->addNamespace('pings');
dcCore::app()->blog->settings->pings->put('pings_active', 1, 'boolean', 'Activate pings plugin', false, true);
dcCore::app()->blog->settings->pings->put('pings_auto', 0, 'boolean', 'Auto pings on 1st publication', false, true);
dcCore::app()->blog->settings->pings->put('pings_uris', $default_pings_uris, 'array', 'Pings services URIs', false, true);

dcCore::app()->setVersion('pings', $version);

return true;
