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

class dcMaintenanceCache extends dcMaintenanceTask
{
	protected $group = 'purge';

	protected function init()
	{
		$this->task 		= __('Empty templates cache directory');
		$this->success 		= __('Templates cache directory emptied.');
		$this->error 		= __('Failed to empty templates cache directory.');
	}

	public function execute()
	{
		$this->core->emptyTemplatesCache();

		return true;
	}
}
