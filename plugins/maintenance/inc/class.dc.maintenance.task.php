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

/**
@ingroup PLUGIN_MAINTENANCE
@nosubgrouping
@brief Maintenance plugin task class.

Every task of maintenance must extend this class.
*/
class dcMaintenanceTask
{
	protected $maintenance;
	protected $core;
	protected $p_url;
	protected $code;
	protected $ts = 0;
	protected $expired = 0;
	protected $ajax = false;
	protected $blog = false;

	protected $id;
	protected $name;
	protected $tab = 'maintenance';
	protected $group = 'other';

	protected $task;
	protected $step;
	protected $error;
	protected $success;

	/**
	 * Constructor.
	 *
	 * If your task required something on construct,
	 * use method init() to do it.
	 *
	 * @param	maintenance	<b>dcMaintenance</b>	dcMaintenance instance
	 * @param	p_url	<b>string</b>	Maintenance plugin url
	 */
	public function __construct($maintenance, $p_url)
	{
		$this->maintenance = $maintenance;
		$this->core = $maintenance->core;
		$this->init();

		$this->p_url = $p_url;
		$this->id = get_class($this);

		if (!$this->name) {
			$this->name = get_class($this);
		}
		if (!$this->error) {
			$this->error = __('Failed to execute task.');
		}
		if (!$this->success) {
			$this->success = __('Task successfully executed.');
		}

		$this->core->blog->settings->addNamespace('maintenance');
		$ts = $this->core->blog->settings->maintenance->get('ts_'.$this->id);

		$this->ts = abs((integer) $ts);
	}

	/**
	 * Initialize task object.
	 *
	 * Better to set translated messages here than
	 * to rewrite constructor.
	 */
	protected function init()
	{
		return null;
	}

	/**
	 * Set $code for task having multiple steps.
	 *
	 * @param	code	<b>integer</b>	Code used for task execution
	 */
	public function code($code)
	{
		$this->code = (integer) $code;
	}

	/**
	 * Get timestamp between maintenances.
	 *
	 * @return	<b>intetger</b>	Timestamp
	 */
	public function ts()
	{
		return $this->ts === false ? false : abs((integer) $this->ts);
	}

	/**
	 * Get task expired.
	 *
	 * This return:
	 * - Timstamp of last update if it expired
	 * - False if it not expired or has no recall time
	 * - Null if it has never been executed
	 *
	 * @return	<b>mixed</b>	Last update
	 */
	public function expired()
	{
		if ($this->expired === 0) {
			if (!$this->ts()) {
				$this->expired = false;
			}
			else {
				$this->expired = null;
				$logs = array();
				foreach($this->maintenance->getLogs() as $id => $log)
				{
					if ($id != $this->id() || $this->blog && !$log['blog']) {
						continue;
					}

					$this->expired = $log['ts'] + $this->ts() < time() ? $log['ts'] : false;
				}
			}
		}
		return $this->expired;
	}

	/**
	 * Get task ID.
	 *
	 * @return	<b>string</b>	Task ID (class name)
	 */
	public function id()
	{
		return $this->id;
	}

	/**
	 * Get task name.
	 *
	 * @return	<b>string</b>	Task name
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Get task tab.
	 *
	 * @return	<b>mixed</b>	Task tab ID or null
	 */
	public function tab()
	{
		return $this->tab;
	}

	/**
	 * Get task group.
	 *
	 * If task required a full tab, 
	 * this must be returned null.
	 * 
	 * @return	<b>mixed</b>	Task group ID or null
	 */
	public function group()
	{
		return $this->group;
	}

	/**
	 * Use ajax
	 *
	 * Is task use maintenance ajax script 
	 * for steps process.
	 *
	 * @return	<b>boolean</b>	Use ajax
	 */
	public function ajax()
	{
		return (boolean) $this->ajax;
	}

	/**
	 * Get task message.
	 *
	 * This message is used on form button.
	 *
	 * @return	<b>string</b>	Message
	 */
	public function task()
	{
		return $this->task;
	}

	/**
	 * Get step message.
	 *
	 * This message is displayed during task step execution.
	 *
	 * @return	<b>mixed</b>	Message or null
	 */
	public function step()
	{
		return $this->step;
	}

	/**
	 * Get success message.
	 *
	 * This message is displayed when task is accomplished.
	 *
	 * @return	<b>mixed</b>	Message or null
	 */
	public function success()
	{
		return $this->success;
	}

	/**
	 * Get error message.
	 *
	 * This message is displayed on error.
	 *
	 * @return	<b>mixed</b>	Message or null
	 */
	public function error()
	{
		return $this->error;
	}

	/**
	 * Get header.
	 *
	 * Headers required on maintenance page.
	 *
	 * @return 	<b>mixed</b>	Message or null
	 */
	public function header()
	{
		return null;
	}

	/**
	 * Get content.
	 *
	 * Content for full tab task.
	 *
	 * @return	<b>string</b>	Tab's content
	 */
	public function content()
	{
		return null;
	}

	/**
	 * Execute task.
	 *
	 * @return	<b>mixed</b>	:
	 *	- FALSE on error,
	 *	- TRUE if task is finished
	 *	- INTEGER if task required a next step
	 */
	public function execute()
	{
		return null;
	}

	/**
	 * Log task execution.
	 *
	 * Sometimes we need to log task execution 
	 * direct from task itself.
	 *
	 */
	protected function log()
	{
		$this->maintenance->setLog($this->id);
	}
}
