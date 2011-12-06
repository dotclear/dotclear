<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of blowupConfig, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$version = $core->plugins->moduleInfo('blowupConfig','version');
if (version_compare($core->getVersion('blowupConfig'),$version,'>=')) {
	return;
}

$settings = new dcSettings($core,null);
$settings->addNamespace('themes');
$settings->themes->put('blowup_style','','string','Blow Up  custom style',false);

$core->setVersion('blowupConfig',$version);
return true;
?>