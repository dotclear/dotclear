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
@defgroup PLUGIN_MAINTENANCE Maintenance plugin for Dotclear
*/

/**
@ingroup PLUGIN_MAINTENANCE
@nosubgrouping
@brief Maintenance plugin core class

Main class to call everything related to maintenance.
*/
class dcMaintenance
{
	private $core;
	private $tasks = array();
	private $groups = array();

	/**
	 * Constructor.
	 *
	 * Here you register tasks and groups for tasks.
	 *
	 * @param	core	<b>dcCore</b>	dcCore instance
	 */
	public function __construct($core)
	{
		$this->core = $core;

		$tasks = new ArrayObject();
		$groups = new ArrayObject();

		# --BEHAVIOR-- dcMaintenanceRegister
		$core->callBehavior('dcMaintenanceRegister', $core, $tasks, $groups);

		$this->init($tasks, $groups);
	}

	/**
	 * Initialize list of groups and tasks.
	 *
	 * @param	tasks	<b>arrayObject</b>	Array of task to register
	 * @param	groups	<b>arrayObject</b>	Array of groups to add
	 */
	public function init($tasks, $groups)
	{
		$this->tasks = $this->groups = array();

		foreach($tasks as $task)
		{
			if (!class_exists($task)) {
				continue;
			}

			$r = new ReflectionClass($task);
			$p = $r->getParentClass();

			if (!$p || $p->name != 'dcMaintenanceTask') {
				continue;
			}

			$this->tasks[$task] = new $task($this->core, 'plugin.php?p=maintenance');
		}

		foreach($groups as $id => $name)
		{
			$this->groups[(string) $id] = (string) $name;
		}
	}

	/**
	 * Get a group name.
	 *
	 * @param	id	<b>string</b> Group ID
	 * @return	<b>mixed</b> Group name or null if not exists
	 */
	public function getGroup($id)
	{
		return array_key_exists($id, $this->groups) ? $this->groups[$id] : null;
	}

	/**
	 * Get groups.
	 *
	 * @return	<b>array</b> Array of groups ID and name
	 */
	public function getGroups()
	{
		return $this->groups;
	}

	/**
	 * Get a task object.
	 *
	 * @param	id	<b>string</b> task ID
	 * @return	<b>mixed</b> Task object or null if not exists
	 */
	public function getTask($id)
	{
		return array_key_exists($id, $this->tasks) ? $this->tasks[$id] : null;
	}

	/**
	 * Get tasks.
	 *
	 * @return	<b>array</b> Array of tasks objects
	 */
	public function getTasks()
	{
		return $this->tasks;
	}

	/**
	 * Get headers for plugin maintenance admin page.
	 *
	 * @return	<b>string</b> Page headers
	 */
	public function getHeaders()
	{
		$res = '';
		foreach($this->tasks as $task)
		{
			$res .= $task->header();
		}
		return $res;
	}

	/**
	 * Set log for a task.
	 *
	 * @param	id	<b>string</b>	Task ID
	 */
	public function setLog($id)
	{
		// Check if taks exists
		if (!$this->getTask($id)) {
			return null;
		}

		// Get logs from this task
		$rs = $this->core->con->select (
			'SELECT log_id '.
			'FROM '.$this->core->prefix.'log '.
			"WHERE log_msg = '".$this->core->con->escape($id)."' ".
			"AND log_table = 'maintenance' "
		);

		$logs = array();
		while ($rs->fetch()) {
			$logs[] = $rs->log_id;
		}

		// Delete old logs
		if (!empty($logs)) {
			$this->core->log->delLogs($logs);
		}

		// Add new log
		$cur = $this->core->con->openCursor($this->core->prefix.'log');

		$cur->log_msg = $id;
		$cur->log_table = 'maintenance';
		$cur->user_id = $this->core->auth->userID();

		$this->core->log->addLog($cur);
	}

	/**
	 * Delete all maintenance logs.
	 */
	public function delLogs()
	{
		// Retrieve logs from this task
		$rs = $this->core->log->getLogs(array(
			'log_table' => 'maintenance',
			'blog_id' => 'all'
		));

		$logs = array();
		while ($rs->fetch()) {
			$logs[] = $rs->log_id;
		}

		// Delete old logs
		if (!empty($logs)) {
			$this->core->log->delLogs($logs);
		}
	}

	/**
	 * Get expired task.
	 *
	 * @return	<b>array</b>	Array of expired Task ID / date
	 */
	public function getExpired()
	{
		// Retrieve logs from this task
		$rs = $this->core->log->getLogs(array(
			'log_table' => 'maintenance',
			'blog_id' => 'all'
		));

		$logs = array();
		while ($rs->fetch()) {
			// Check if task exists
			if (($task = $this->getTask($rs->log_msg)) !== null) {
				// Check if tasks expired
				if (strtotime($rs->log_dt) + $task->ts() < time()) {
					$logs[$rs->log_msg] = $rs->log_dt;
				}
			}
		}
		return $logs;
	}
}
