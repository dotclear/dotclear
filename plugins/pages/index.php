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

$act = (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'page') ? 'page' : 'list';


if ($act == 'page') {
	include dirname(__FILE__).'/page.php';
} else {
	include dirname(__FILE__).'/list.php';
}

?>