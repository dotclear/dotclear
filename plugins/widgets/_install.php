<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of widgets, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$version = $core->plugins->moduleInfo('widgets','version');
if (version_compare($core->getVersion('widgets'),$version,'>=')) {
	return;
}

require dirname(__FILE__).'/_default_widgets.php';

$settings =& $core->blog->settings;
$settings->addNamespace('widgets');
if ($settings->widgets->widgets_nav != null) {
	$settings->widgets->put('widgets_nav',dcWidgets::load($settings->widgets->widgets_nav)->store());
} else {
	$settings->widgets->put('widgets_nav','','string','Navigation widgets',false);
}
if ($settings->widgets->widgets_extra != null) {
	$settings->widgets->put('widgets_extra',dcWidgets::load($settings->widgets->widgets_extra)->store());
} else {
	$settings->widgets->put('widgets_extra','','string','Extra widgets',false);
}
if ($settings->widgets->widgets_custom != null) {
	$settings->widgets->put('widgets_custom',dcWidgets::load($settings->widgets->widgets_custom)->store());
} else {
	$settings->widgets->put('widgets_custom','','string','Custom widgets',false);
}
$core->setVersion('widgets',$version);
return true;
?>