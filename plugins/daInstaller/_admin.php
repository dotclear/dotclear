<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of daInstaller, a plugin for DotClear2.
# Copyright (c) 2008-2011 Tomtom, Pep and contributors, for DotAddict.org.
# All rights reserved.
#
# ***** END LICENSE BLOCK *****
if (!defined('DC_CONTEXT_ADMIN')) { return; }

/**
 * Callback to add an icon on Dotclear2's dashboard. Trying to do so the "smart way".
 * If updates are available, let's notify that fact !
 *
 */
function adminDashboardFavs($core,$favs)
{
	if ($core->auth->isSuperAdmin())
	{
		$id = 'dainstaller';
		$title = __('DotAddict.org Installer');
		$link  = 'plugin.php?p=daInstaller';
		$icon  = 'index.php?pf=daInstaller/icon.png';
		$icon_big  = 'index.php?pf=daInstaller/icon-big.png';
		$favs[$id] = new ArrayObject(array($id,$title,$link,$icon,$icon_big,null,null,null));
	}
}
function adminDashboardFavsIcon($core,$name,$icon)
{
	if ($name === 'dainstaller') {
		$daInstaller = new daInstaller($core);
		if ($daInstaller->check()) {
			$upd_plugins = $daInstaller->getModules('plugins',true);
			$upd_themes = $daInstaller->getModules('themes',true);
			if (
				(is_array($upd_plugins) && count($upd_plugins)) ||
				(is_array($upd_themes) && count($upd_themes))
			) {
				$icon[0] .= '<br />'.__('Updates are available');
				$icon[1] .= '&amp;tab=update';
				$icon[2] = 'index.php?pf=daInstaller/icon-big-update.png';
			}
		}
	}
}

$core->addBehavior('adminDashboardFavs','adminDashboardFavs');
$core->addBehavior('adminDashboardFavsIcon','adminDashboardFavsIcon');

$_menu['System']->addItem(
	__('DotAddict.org Installer'),
	'plugin.php?p=daInstaller',
	'index.php?pf=daInstaller/icon.png',
	preg_match('/plugin.php\?p=daInstaller(&.*)?$/',
	$_SERVER['REQUEST_URI']),
	$core->auth->isSuperAdmin()
);

?>