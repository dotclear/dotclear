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

$_menu['Plugins']->addItem('about:config','plugin.php?p=aboutConfig','index.php?pf=aboutConfig/icon.png',
		preg_match('/plugin.php\?p=aboutConfig(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin());
?>