<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior('adminCurrentThemeDetails','blowup_config_details');

if (!isset($__resources['help']['blowupConfig'])) {
	$__resources['help']['blowupConfig'] = dirname(__FILE__).'/help.html';
}

function blowup_config_details($core,$id)
{
	if ($id == 'default' && $core->auth->check('admin',$core->blog->id)) {
		return '<p><a href="plugin.php?p=blowupConfig" class="button">'.__('Theme configuration').'</a></p>';
	}
}
?>