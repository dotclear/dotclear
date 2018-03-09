<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

/**
Main class to call everything related to maintenance.
 */
class dcMaintenance
{
    public $core;
    public $p_url;

    private $tasks  = array();
    private $tabs   = array();
    private $groups = array();
    private $logs   = null;

    /**
     * Constructor.
     *
     * @param    core    <b>dcCore</b>    dcCore instance
     */
    public function __construct($core)
    {
        $this->core  = $core;
        $this->p_url = $core->adminurl->get('admin.plugin.maintenance');
        $logs        = $this->getLogs();
        $this->init();
    }

    /**
     * Initialize list of tabs and groups and tasks.
     *
     * To register a tab or group or task,
     * use behavior dcMaintenanceInit then a method of
     * dcMaintenance like addTab('myTab', ...).
     */
    protected function init()
    {
        # --BEHAVIOR-- dcMaintenanceInit
        $this->core->callBehavior('dcMaintenanceInit', $this);
    }

    /// @name Tab methods
    //@{
    /**
     * Add a tab.
     *
     * @param    id        <b>string<b> Tab ID
     * @param    name    <b>string<b> Tab name
     * @param    options    <b>string<b> Options
     * @return <b>dcMaintenance</b>    Self
     */
    public function addTab($id, $name, $options = array())
    {
        $this->tabs[$id] = new dcMaintenanceDescriptor($id, $name, $options);

        return $this;
    }

    /**
     * Get a tab.
     *
     * @param    id    <b>string</b> Tab ID
     * @return    <b>object</b> dcMaintenanceDescriptor of a tab
     */
    public function getTab($id)
    {
        return array_key_exists($id, $this->tabs) ? $this->tabs[$id] : null;
    }

    /**
     * Get tabs.
     *
     * @return    <b>array</b> Array of tabs ID and name
     */
    public function getTabs()
    {
        return $this->tabs;
    }
    //@}

    /// @name Group methods
    //@{
    /**
     * Add a group.
     *
     * @param    id        <b>string<b> Group ID
     * @param    name    <b>string<b> Group name
     * @param    options    <b>string<b> Options
     * @return <b>dcMaintenance</b>    Self
     */
    public function addGroup($id, $name, $options = array())
    {
        $this->groups[$id] = new dcMaintenanceDescriptor($id, $name, $options);

        return $this;
    }

    /**
     * Get a group.
     *
     * @param    id    <b>string</b> Group ID
     * @return    <b>object</b> dcMaintenanceDescriptor of a group
     */
    public function getGroup($id)
    {
        return array_key_exists($id, $this->groups) ? $this->groups[$id] : null;
    }

    /**
     * Get groups.
     *
     * @return    <b>array</b> Array of groups ID and descriptor
     */
    public function getGroups()
    {
        return $this->groups;
    }
    //@}

    /// @name Task methods
    //@{
    /**
     * Add a task.
     *
     * @param    task <b>mixed<b> Class name or object
     * @return    <b>boolean</b>    True if it is added
     * @return <b>dcMaintenance</b>    Self
     */
    public function addTask($task)
    {
        if (class_exists($task) && is_subclass_of($task, 'dcMaintenanceTask')) {
            $this->tasks[$task] = new $task($this);
        }

        return $this;
    }

    /**
     * Get a task object.
     *
     * @param    id    <b>string</b> task ID
     * @return    <b>mixed</b> Task object or null if not exists
     */
    public function getTask($id)
    {
        return array_key_exists($id, $this->tasks) ? $this->tasks[$id] : null;
    }

    /**
     * Get tasks.
     *
     * @return    <b>array</b> Array of tasks objects
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Get headers for plugin maintenance admin page.
     *
     * @return    <b>string</b> Page headers
     */
    public function getHeaders()
    {
        $res = '';
        foreach ($this->tasks as $task) {
            $res .= $task->header();
        }
        return $res;
    }
    //@}

    /// @name Log methods
    //@{
    /**
     * Set log for a task.
     *
     * @param    id    <b>string</b>    Task ID
     */
    public function setLog($id)
    {
        // Check if taks exists
        if (!$this->getTask($id)) {
            return;
        }

        // Get logs from this task
        $rs = $this->core->con->select(
            'SELECT log_id ' .
            'FROM ' . $this->core->prefix . 'log ' .
            "WHERE log_msg = '" . $this->core->con->escape($id) . "' " .
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
        $cur = $this->core->con->openCursor($this->core->prefix . 'log');

        $cur->log_msg   = $id;
        $cur->log_table = 'maintenance';
        $cur->user_id   = $this->core->auth->userID();

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
            'blog_id'   => 'all'
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
     * Get logs
     *
     * Return array(
     *        task id => array(
     *            timestamp of last execution,
     *            logged on current blog or not
     *        )
     * )
     *
     * @return    <b>array</b> List of logged tasks
     */
    public function getLogs()
    {
        if ($this->logs === null) {
            $rs = $this->core->log->getLogs(array(
                'log_table' => 'maintenance',
                'blog_id'   => 'all'
            ));

            $this->logs = array();
            while ($rs->fetch()) {
                $this->logs[$rs->log_msg] = array(
                    'ts'   => strtotime($rs->log_dt),
                    'blog' => $rs->blog_id == $this->core->blog->id
                );
            }
        }

        return $this->logs;
    }
    //@}
}
