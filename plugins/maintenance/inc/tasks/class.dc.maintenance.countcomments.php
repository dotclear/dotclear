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

class dcMaintenanceCountcomments extends dcMaintenanceTask
{
	protected $group = 'index';

	protected function init()
	{
		$this->task 		= __('Count again comments and trackbacks');
		$this->success 		= __('Comments and trackback counted.');
		$this->error 		= __('Failed to count comments and trackbacks.');
	}

	public function execute()
	{
		$this->core->countAllComments();

		return true;
	}
}
