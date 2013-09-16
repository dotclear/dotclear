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

/**
@ingroup PLUGIN_MAINTENANCE
@nosubgrouping
@brief Maintenance plugin rest service class.

Serve maintenance methods via Dotclear's rest API
*/
class dcMaintenanceRest
{
	/**
	 * Serve method to do step by step task for maintenance.
	 *
	 * @param	core	<b>dcCore</b>	dcCore instance
	 * @param	get		<b>array</b>	cleaned $_GET
	 * @param	post	<b>array</b>	cleaned $_POST
	 *
	 * @return	<b>xmlTag</b>	XML representation of response
	 */
	public static function step($core, $get, $post)
	{
		if (!isset($post['task'])) {
			throw new Exception('No task ID');
		}
		if (!isset($post['code'])) {
			throw new Exception('No code ID');
		}

		$maintenance = new dcMaintenance($core);
		if (($task = $maintenance->getTask($post['task'])) === null) {
			throw new Exception('Unknow task ID');
		}

		$task->code((integer) $post['code']);
		if (($code = $task->execute()) === true) {
			$code = 0;
		}

		$rsp = new xmlTag('step');
		$rsp->code = $code;
		$rsp->title = html::escapeHTML($task->success());

		return $rsp;
	}
}
