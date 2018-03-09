<?php
/**
 * @brief blowupConfig, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$version = $core->plugins->moduleInfo('blowupConfig', 'version');
if (version_compare($core->getVersion('blowupConfig'), $version, '>=')) {
    return;
}

$settings = new dcSettings($core, null);
$settings->addNamespace('themes');
$settings->themes->put('blowup_style', '', 'string', 'Blow Up  custom style', false);

$core->setVersion('blowupConfig', $version);
return true;
