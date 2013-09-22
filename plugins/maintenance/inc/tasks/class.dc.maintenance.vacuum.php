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
if (!defined('DC_RC_PATH')) { return; }

class dcMaintenanceVacuum extends dcMaintenanceTask
{
	protected $group = 'optimize';

	protected function init()
	{
		$this->task 		= __('optimize tables');
		$this->success 		= __('Optimization successful.');
		$this->error 		= __('Failed to optimize tables.');
	}

	public function execute()
	{
		$schema = dbSchema::init($this->core->con);

		foreach ($schema->getTables() as $table)
		{
			if (strpos($table, $this->core->prefix) === 0) {
				$this->core->con->vacuum($table);
			}
		}

		return true;
	}
}
