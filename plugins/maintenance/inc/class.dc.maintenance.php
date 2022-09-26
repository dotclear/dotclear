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
if (!defined('DC_RC_PATH')) {
    return;
}

/**
Main class to call everything related to maintenance.
 */
class dcMaintenance
{
    private $tasks  = [];
    private $tabs   = [];
    private $groups = [];
    private $logs   = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->logs = $this->getLogs();
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
        dcCore::app()->callBehavior('dcMaintenanceInit', $this);
    }

    /// @name Tab methods
    //@{
    /**
     * Adds a tab.
     *
     * @param      string  $id       The identifier
     * @param      string  $name     The name
     * @param      array   $options  The options
     *
     * @return     self
     */
    public function addTab($id, $name, $options = [])
    {
        $this->tabs[$id] = new dcMaintenanceDescriptor($id, $name, $options);

        return $this;
    }

    /**
     * Gets the tab.
     *
     * @param      string  $id     The identifier
     *
     * @return     dcMaintenanceDescriptor|null  The tab.
     */
    public function getTab($id)
    {
        return array_key_exists($id, $this->tabs) ? $this->tabs[$id] : null;
    }

    /**
     * Gets the tabs.
     *
     * @return     array  The tabs.
     */
    public function getTabs()
    {
        return $this->tabs;
    }
    //@}

    /// @name Group methods
    //@{
    /**
     * Adds a group.
     *
     * @param      string  $id       The identifier
     * @param      string  $name     The name
     * @param      array   $options  The options
     *
     * @return     self
     */
    public function addGroup($id, $name, $options = [])
    {
        $this->groups[$id] = new dcMaintenanceDescriptor($id, $name, $options);

        return $this;
    }

    /**
     * Gets the group.
     *
     * @param      string  $id     The identifier
     *
     * @return     dcMaintenanceDescriptor|null  The group.
     */
    public function getGroup($id)
    {
        return array_key_exists($id, $this->groups) ? $this->groups[$id] : null;
    }

    /**
     * Gets the groups.
     *
     * @return     array  The groups.
     */
    public function getGroups()
    {
        return $this->groups;
    }
    //@}

    /// @name Task methods
    //@{
    /**
     * Adds a task.
     *
     * @param      mixed  $task   The task, Class name or object
     *
     * @return     self
     */
    public function addTask($task)
    {
        if (class_exists($task) && is_subclass_of($task, 'dcMaintenanceTask')) {
            $this->tasks[$task] = new $task($this);
        }

        return $this;
    }

    /**
     * Gets the task.
     *
     * @param      string  $id     The identifier
     *
     * @return     mixed  The task.
     */
    public function getTask($id)
    {
        return array_key_exists($id, $this->tasks) ? $this->tasks[$id] : null;
    }

    /**
     * Gets the tasks.
     *
     * @return     array  The tasks.
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Gets the headers for plugin maintenance admin page.
     *
     * @return     string  The headers.
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
     * Sets the log for a task.
     *
     * @param      string  $id     Task ID
     */
    public function setLog($id)
    {
        // Check if taks exists
        if (!$this->getTask($id)) {
            return;
        }

        // Get logs from this task
        $rs = dcCore::app()->con->select(
            'SELECT log_id ' .
            'FROM ' . dcCore::app()->prefix . dcLog::LOG_TABLE_NAME . ' ' .
            "WHERE log_msg = '" . dcCore::app()->con->escape($id) . "' " .
            "AND log_table = 'maintenance' "
        );

        $logs = [];
        while ($rs->fetch()) {
            $logs[] = $rs->log_id;
        }

        // Delete old logs
        if (!empty($logs)) {
            dcCore::app()->log->delLogs($logs);
        }

        // Add new log
        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME);

        $cur->log_msg   = $id;
        $cur->log_table = 'maintenance';
        $cur->user_id   = dcCore::app()->auth->userID();

        dcCore::app()->log->addLog($cur);
    }

    /**
     * Delete all maintenance logs.
     */
    public function delLogs()
    {
        // Retrieve logs from this task
        $rs = dcCore::app()->log->getLogs([
            'log_table' => 'maintenance',
            'blog_id'   => '*',
        ]);

        $logs = [];
        while ($rs->fetch()) {
            $logs[] = $rs->log_id;
        }

        // Delete old logs
        if (!empty($logs)) {
            dcCore::app()->log->delLogs($logs);
        }
    }

    /**
     * Get logs
     *
     * Return [
     *        task id => [
     *            timestamp of last execution,
     *            logged on current blog or not
     *        ]
     * ]
     *
     * @return    array List of logged tasks
     */
    public function getLogs()
    {
        if ($this->logs === null) {
            $rs = dcCore::app()->log->getLogs([
                'log_table' => 'maintenance',
                'blog_id'   => '*',
            ]);

            $this->logs = [];
            while ($rs->fetch()) {
                $this->logs[$rs->log_msg] = [
                    'ts'   => strtotime($rs->log_dt),
                    'blog' => $rs->blog_id == dcCore::app()->blog->id,
                ];
            }
        }

        return $this->logs;
    }
    //@}
}
