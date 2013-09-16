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
}
