<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of importExport, a plugin for DotClear2.
#
# Copyright (c) 2003-2012 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$_menu['Plugins']->addItem(
	__('Import/Export'),
	'plugin.php?p=importExport',
	'index.php?pf=importExport/icon.png',
	preg_match('/plugin.php\?p=importExport(&.*)?$/',$_SERVER['REQUEST_URI']),
	$core->auth->check('admin',$core->blog->id)
);

$core->addBehavior('adminDashboardFavorites','importExportDashboardFavorites');

function importExportDashboardFavorites($core,$favs)
{
	$favs->register('importExport', array(
		'title' => __('Import/Export'),
		'url' => 'plugin.php?p=importExport',
		'small-icon' => 'index.php?pf=importExport/icon.png',
		'large-icon' => 'index.php?pf=importExport/icon-big.png',
		'permissions' => 'admin'
	));
}

$core->addBehavior('dcMaintenanceInit', 'ieMaintenanceInit');

function ieMaintenanceInit($maintenance)
{
	$maintenance
	->addTask('ieMaintenanceExportblog')
	->addTask('ieMaintenanceExportfull')
	;
}
