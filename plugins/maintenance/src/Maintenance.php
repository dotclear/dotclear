<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;

/**
 * @brief   The maintenance handler.
 * @ingroup maintenance
 */
class Maintenance
{
    /**
     * Stack of task.
     *
     * @var     array<string, MaintenanceTask>   $tasks
     */
    private array $tasks = [];

    /**
     * Stack of tabs.
     *
     * @var     array<string, MaintenanceDescriptor>   $tabs
     */
    private array $tabs = [];

    /**
     * Stack of groups.
     *
     * @var     array<string, MaintenanceDescriptor>   $groups
     */
    private array $groups = [];

    /**
     * Logs.
     *
     * @var     null|array<string, array<string, mixed>>  $logs
     */
    private ?array $logs = null;

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
     * Maintenance like addTab('myTab', ...).
     */
    protected function init(): void
    {
        # --BEHAVIOR-- dcMaintenanceInit -- Maintenance
        App::behavior()->callBehavior('dcMaintenanceInit', $this);
    }

    /// @name Tab methods
    //@{
    /**
     * Adds a tab.
     *
     * @param   string                  $id         The identifier
     * @param   string                  $name       The name
     * @param   array<string, string>   $options    The options
     */
    public function addTab(string $id, string $name, array $options = []): static
    {
        $this->tabs[$id] = new MaintenanceDescriptor($id, $name, $options);

        return $this;
    }

    /**
     * Gets the tab.
     *
     * @param   string  $id     The identifier
     *
     * @return  MaintenanceDescriptor|null  The tab.
     */
    public function getTab(string $id)
    {
        return $this->tabs[$id] ?? null;
    }

    /**
     * Gets the tabs.
     *
     * @return  array<string, MaintenanceDescriptor>   The tabs.
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }
    //@}

    /// @name Group methods
    //@{
    /**
     * Adds a group.
     *
     * @param   string                  $id         The identifier
     * @param   string                  $name       The name
     * @param   array<string, string>   $options    The options
     */
    public function addGroup(string $id, string $name, array $options = []): static
    {
        $this->groups[$id] = new MaintenanceDescriptor($id, $name, $options);

        return $this;
    }

    /**
     * Gets the group.
     *
     * @param   string  $id     The identifier
     *
     * @return  MaintenanceDescriptor|null  The group.
     */
    public function getGroup(string $id): ?MaintenanceDescriptor
    {
        return $this->groups[$id] ?? null;
    }

    /**
     * Gets the groups.
     *
     * @return  array<string, MaintenanceDescriptor>   The groups.
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
    //@}

    /// @name Task methods
    //@{
    /**
     * Adds a task.
     *
     * @param   mixed   $task   The task, Class name or object
     */
    public function addTask($task): static
    {
        if (is_subclass_of($task, MaintenanceTask::class)) {
            $tmp                     = new $task($this);
            $this->tasks[$tmp->id()] = $tmp;
        }

        return $this;
    }

    /**
     * Gets the task.
     *
     * @param   string  $id     The identifier
     *
     * @return  null|MaintenanceTask   The task.
     */
    public function getTask(string $id): ?MaintenanceTask
    {
        return $this->tasks[$id] ?? null;
    }

    /**
     * Gets the tasks.
     *
     * @return  array<string, MaintenanceTask>   The tasks.
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Gets the headers for plugin maintenance admin page.
     *
     * @return  string  The headers.
     */
    public function getHeaders(): string
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
     * @param   string  $id     Task ID
     */
    public function setLog(string $id): void
    {
        // Check if taks exists
        if (!$this->getTask($id) instanceof MaintenanceTask) {
            return;
        }

        // Get logs from this task
        $sql = new SelectStatement();
        $rs  = $sql
            ->column('log_id')
            ->from(App::con()->prefix() . App::log()::LOG_TABLE_NAME)
            ->where('log_msg = ' . $sql->quote($id))
            ->and('log_table = ' . $sql->quote('maintenance'))
            ->select();

        $logs = [];
        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                $logs[] = $rs->log_id;
            }
        }

        // Delete old logs
        if ($logs !== []) {
            App::log()->delLogs($logs);
        }

        // Add new log
        $cur = App::log()->openLogCursor();

        $cur->log_msg   = $id;
        $cur->log_table = 'maintenance';
        $cur->user_id   = App::auth()->userID();

        App::log()->addLog($cur);
    }

    /**
     * Delete all maintenance logs.
     */
    public function delLogs(): void
    {
        // Retrieve logs from this task
        $rs = App::log()->getLogs([
            'log_table' => 'maintenance',
            'blog_id'   => '*',
        ]);

        $logs = [];
        while ($rs->fetch()) {
            $logs[] = $rs->log_id;
        }

        // Delete old logs
        if ($logs !== []) {
            App::log()->delLogs($logs);
        }
    }

    /**
     * Get logs.
     *
     * Return [
     *        task id => [
     *            timestamp of last execution,
     *            logged on current blog or not
     *        ]
     * ]
     *
     * @return  array<string, array<string, mixed>>   List of logged tasks
     */
    public function getLogs(): array
    {
        if ($this->logs === null) {
            $rs = App::log()->getLogs([
                'log_table' => 'maintenance',
                'blog_id'   => '*',
            ]);

            $this->logs = [];
            while ($rs->fetch()) {
                $this->logs[$rs->log_msg] = [   // @phpstan-ignore-line
                    'ts'   => strtotime($rs->log_dt),
                    'blog' => $rs->blog_id == App::blog()->id(),
                ];
            }
        }

        return $this->logs; // @phpstan-ignore-line
    }
    //@}
}
