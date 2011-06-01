<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$_menu['Plugins']->addItem(__('Pings'),'plugin.php?p=pings','index.php?pf=pings/icon.png',
		preg_match('/plugin.php\?p=pings/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin());

$__autoload['pingsAPI'] = dirname(__FILE__).'/lib.pings.php';
$__autoload['pingsBehaviors'] = dirname(__FILE__).'/lib.pings.php';

# Create settings if they don't exist
if (!array_key_exists('pings',$core->blog->settings->dumpNamespaces()))
{
	$default_pings_uris = array(
		'Ping-o-Matic!' => 'http://rpc.pingomatic.com/',
		'Google Blog Search' => 'http://blogsearch.google.com/ping/RPC2'
	);
		
	$core->blog->settings->addNamespace('pings');
	$core->blog->settings->pings->put('pings_active',1,'boolean','Activate pings plugin',true,true);
	$core->blog->settings->pings->put('pings_uris',serialize($default_pings_uris),'string','Pings services URIs',true,true);
}

$core->addBehavior('adminPostHeaders',array('pingsBehaviors','pingJS'));
$core->addBehavior('adminPostFormSidebar',array('pingsBehaviors','pingsForm'));
$core->addBehavior('adminAfterPostCreate',array('pingsBehaviors','doPings'));
$core->addBehavior('adminAfterPostUpdate',array('pingsBehaviors','doPings'));

$core->addBehavior('adminDashboardFavs','pingDashboardFavs');

function pingDashboardFavs($core,$favs)
{
	$favs['pings'] = new ArrayObject(array('pings','Pings','plugin.php?p=pings',
		'index.php?pf=pings/icon.png','index.php?pf=pings/icon-big.png',
		null,null,null));
}
?>