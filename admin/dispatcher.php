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

# Delete users
if (!empty($_REQUEST['dispatch_action']))
{
	if ($_REQUEST['dispatch_action'] == 'deleteuser')
	{
		if (!empty($_REQUEST['user_id'])) {
			$delete_users = $_REQUEST['user_id'];
		}
		
		include dirname(__FILE__).'/users.php';
		exit;
	}
	elseif ($_REQUEST['dispatch_action'] == 'setpermissions')
	{
		include dirname(__FILE__).'/permissions_blog.php';
		exit;
	}
}

echo '<p>What the hell are you doing here?</p>';
exit;
?>