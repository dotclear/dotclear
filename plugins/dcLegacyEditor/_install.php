<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$version = $core->plugins->moduleInfo('dcLegacyEditor', 'version');
if (version_compare($core->getVersion('dcLegacyEditor'), $version, '>=')) {
    return;
}

$settings = $core->blog->settings;
$settings->addNamespace('dclegacyeditor');
$settings->dclegacyeditor->put('active', true, 'boolean', 'dcLegacyEditor plugin activated ?', false, true);

$core->setVersion('dcLegacyEditor', $version);
return true;
