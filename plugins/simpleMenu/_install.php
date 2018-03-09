<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$version = $core->plugins->moduleInfo('simpleMenu', 'version');
if (version_compare($core->getVersion('simpleMenu'), $version, '>=')) {
    return;
}

# Menu par défaut
$blog_url     = html::stripHostURL($core->blog->url);
$menu_default = array(
    array('label' => 'Home', 'descr' => 'Recent posts', 'url' => $blog_url, 'targetBlank' => false),
    array('label' => 'Archives', 'descr' => '', 'url' => $blog_url . $core->url->getURLFor('archive'), 'targetBlank' => false)
);
$core->blog->settings->system->put('simpleMenu', $menu_default, 'array', 'simpleMenu default menu', false, true);
$core->blog->settings->system->put('simpleMenu_active', true, 'boolean', 'Active', false, true);

$core->setVersion('simpleMenu', $version);
return true;
