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
	protected $core;
	protected $p_url;
	protected $code;
	protected $ts = 604800; // one week

	protected $id;
	protected $name;
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
	 * @param	core	<b>dcCore</b>	dcCore instance
	 * @param	p_url	<b>string</b>	Maintenance plugin url
	 */
	public function __construct($core, $p_url)
	{
		$this->core =& $core;
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
		return abs((integer) $this->ts);
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
}
