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

class dcMaintenanceCSP extends dcMaintenanceTask
{
	protected $group = 'purge';

	protected function init()
	{
		$this->task 		= __('Delete the Content-Security-Policy report file');
		$this->success 		= __('Content-Security-Policy report file has been deleted.');
		$this->error 		= __('Failed to delete the Content-Security-Policy report file.');

		$this->description = __("Remove the Content-Security-Policy report file.");
	}

	public function execute()
	{
		$csp_file = path::real(DC_VAR).'/csp/csp_report.json';
		if (file_exists($csp_file)) {
			unlink($csp_file);
		}

		return true;
	}
}
