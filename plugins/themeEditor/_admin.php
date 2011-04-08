<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

if (!isset($__resources['help']['themeEditor'])) {
	$__resources['help']['themeEditor'] = dirname(__FILE__).'/help.html';
}

$core->addBehavior('adminCurrentThemeDetails','theme_editor_details');

function theme_editor_details($core,$id)
{
	if ($id != 'default' && $core->auth->isSuperAdmin()) {
		return '<p><a href="plugin.php?p=themeEditor" class="button">'.__('Theme Editor').'</a></p>';
	}
}
?>