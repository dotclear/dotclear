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

$_menu['Plugins']->addItem('user:preferences','plugin.php?p=userPref','index.php?pf=userPref/icon.png',
		preg_match('/plugin.php\?p=userPref(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin());
?>