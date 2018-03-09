<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$version = $core->plugins->moduleInfo('widgets', 'version');
if (version_compare($core->getVersion('widgets'), $version, '>=')) {
    return;
}

require dirname(__FILE__) . '/_default_widgets.php';

$settings = &$core->blog->settings;
$settings->addNamespace('widgets');
if ($settings->widgets->widgets_nav != null) {
    $settings->widgets->put('widgets_nav', dcWidgets::load($settings->widgets->widgets_nav)->store());
} else {
    $settings->widgets->put('widgets_nav', '', 'string', 'Navigation widgets', false);
}
if ($settings->widgets->widgets_extra != null) {
    $settings->widgets->put('widgets_extra', dcWidgets::load($settings->widgets->widgets_extra)->store());
} else {
    $settings->widgets->put('widgets_extra', '', 'string', 'Extra widgets', false);
}
if ($settings->widgets->widgets_custom != null) {
    $settings->widgets->put('widgets_custom', dcWidgets::load($settings->widgets->widgets_custom)->store());
} else {
    $settings->widgets->put('widgets_custom', '', 'string', 'Custom widgets', false);
}
$core->setVersion('widgets', $version);
return true;
