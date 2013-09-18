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

class ieMaintenanceExportblog extends dcMaintenanceTask
{
	protected $tab = 'backup';
	protected $group = 'zipblog';

	protected $export_name;
	protected $export_type;

	protected function init()
	{
		$this->name = __('Database export');
		$this->task = __('Download database of current blog');
		$this->step = __('Zip file ready to be downloaded.');

		$this->export_name = html::escapeHTML($this->core->blog->id.'-backup.txt');
		$this->export_type = 'export_blog';
	}

	public function execute()
	{
		// 1) Create zip file
		if (!isset($_SESSION['export_file']) || !file_exists($_SESSION['export_file'])) {
			// Bad hack to skip one step
			$_POST['file_name'] = date('Y-m-d-H-i-').$this->export_name;
			$_POST['file_zip'] = 1;

			// This process make an http redirect
			$ie = new maintenanceDcExportFlat($this->core);
			$ie->setURL($this->id);
			$ie->process($this->export_type);
		}
		// 2) Download zip file
		else {
			// Log task execution here as we sent file and stop script
			$this->log();

			// This process send file by http and stop script
			$ie = new maintenanceDcExportFlat($this->core);
			$ie->setURL($this->id);
			$ie->process('ok');
		}

		return null;
	}

	public function step()
	{
		return isset($_SESSION['export_file']) && file_exists($_SESSION['export_file']) ?
			$this->step : null;
	}
}

class ieMaintenanceExportfull extends dcMaintenanceTask
{
	protected $tab = 'backup';
	protected $group = 'zipfull';

	protected $export_name;
	protected $export_type;

	protected function init()
	{
		$this->name = __('Database export');
		$this->task = __('Download database of all blogs');
		$this->step = __('Zip file ready to be downloaded.');

		$this->export_name = 'dotclear-backup.txt';
		$this->export_type = 'export_all';
	}

	public function execute()
	{
		// 1) Create zip file
		if (!isset($_SESSION['export_file']) || !file_exists($_SESSION['export_file'])) {
			// Bad hack to skip one step
			$_POST['file_name'] = date('Y-m-d-H-i-').$this->export_name;
			$_POST['file_zip'] = 1;

			// This process make an http redirect
			$ie = new maintenanceDcExportFlat($this->core);
			$ie->setURL($this->id);
			$ie->process($this->export_type);
		}
		// 2) Download zip file
		else {
			// Log task execution here as we sent file and stop script
			$this->log();

			// This process send file by http and stop script
			$ie = new maintenanceDcExportFlat($this->core);
			$ie->setURL($this->id);
			$ie->process('ok');
		}

		return null;
	}

	public function step()
	{
		return isset($_SESSION['export_file']) && file_exists($_SESSION['export_file']) ?
			$this->step : null;
	}
}

class maintenanceDcExportFlat extends dcExportFlat
{
	/**
	 * Set redirection URL of bakcup process.
	 *
	 * Bad hack to change redirection of dcExportFlat::process()
	 *
	 * @param	id	<b>string</b>	Task id
	 */
	public function setURL($id)
	{
		$this->url = sprintf('plugin.php?p=maintenance&task=%s', $id);
	}
}
