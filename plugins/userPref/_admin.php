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

// Register admin URL base of plugin
$core->adminurl->registercopy('admin.plugin.user.pref','admin.plugin',array('p' => 'userPref'));

$_menu['System']->addItem('user:preferences',
		$core->adminurl->get('admin.plugin.user.pref'),
		$core->adminurl->decode('load.plugin.file',array('pf' => 'userPref/icon.png')),
		preg_match('/'.preg_quote($core->adminurl->get('admin.plugin.user.pref')).'(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin());
