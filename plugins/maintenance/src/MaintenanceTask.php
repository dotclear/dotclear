<?php
/**
 * @brief Maintenance plugin task class.
 *
 * Every task of maintenance must extend this class.
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use Dotclear\App;

class MaintenanceTask
{
    /**
     * Maintenance object instance
     *
     * @var Maintenance
     */
    protected $maintenance;

    /**
     * Task code
     *
     * @var int|null|bool
     */
    protected $code;

    /**
     * Task timestamp
     *
     * @var bool|int
     */
    protected $ts = 0;

    /**
     * Expired flag
     *
     * @var null|int|bool
     */
    protected $expired = 0;

    /**
     * Task use AJAX
     *
     * @var bool
     */
    protected $ajax = false;

    /**
     * Task limited to current blog
     *
     * @var bool
     */
    protected $blog = false;

    /**
     * Task permissions
     *
     * @var null|string
     */
    protected $perm = null;

    /**
     * Task ID (class name)
     *
     * @var null|string
     */
    protected $id = null;

    /**
     * Task name
     *
     * @var string
     */
    protected $name;

    /**
     * Task description
     *
     * @var string
     */
    protected $description;

    /**
     * Task tab container
     *
     * @var string
     */
    protected $tab = 'maintenance';

    /**
     * Task group container
     *
     * @var string
     */
    protected $group = 'other';

    /**
     * Task message
     *
     * @var string
     */
    protected $task;

    /**
     * Task step
     *
     * @var null|string
     */
    protected $step;

    /**
     * Task error message
     *
     * @var string
     */
    protected $error;

    /**
     * Task success message
     *
     * @var string
     */
    protected $success;

    /**
     * Constructor.
     *
     * If your task required something on construct,
     * use method init() to do it.
     *
     * @param      Maintenance  $maintenance  The maintenance
     */
    public function __construct(Maintenance $maintenance)
    {
        $this->maintenance = $maintenance;
        $this->init();

        if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $this->id)) {
            // Set id if not yet defined
            $path     = explode('\\', static::class);
            $this->id = array_pop($path);
        }

        if ($this->perm() === null && !App::auth()->isSuperAdmin()
            || !App::auth()->check($this->perm(), App::blog()->id)) {
            return;
        }

        if (!$this->name) {
            $this->name = $this->id;
        }
        if (!$this->error) {
            $this->error = __('Failed to execute task.');
        }
        if (!$this->success) {
            $this->success = __('Task successfully executed.');
        }

        $this->ts = abs((int) My::settings()->get('ts_' . $this->id));
    }

    /**
     * Initialize task object.
     *
     * Better to set translated messages here than
     * to rewrite constructor.
     */
    protected function init(): void
    {
    }

    /**
     * Get task permission.
     *
     * Return user permission required to run this task
     * or null for super admin.
     *
     * @return null|string Permission.
     */
    public function perm(): ?string
    {
        return $this->perm;
    }

    /**
     * Get task scope.
     *.
     * Is task limited to current blog.
     *
     * @return boolean Limit to blog
     */
    public function blog(): bool
    {
        return $this->blog;
    }

    /**
     * Set $code for task having multiple steps.
     *
     * @param    integer $code    Code used for task execution
     */
    public function code($code): void
    {
        $this->code = $code;
    }

    /**
     * Get timestamp between maintenances.
     *
     * @return     false|int  Timestamp
     */
    public function ts()
    {
        return $this->ts === false ? false : abs((int) $this->ts);
    }

    /**
     * Get task expired.
     *
     * This return:
     * - Timestamp of last update if it expired
     * - False if it not expired or has no recall time
     * - Null if it has never been executed
     *
     * @return    null|bool|int    Last update
     */
    public function expired()
    {
        if ($this->expired === 0) {
            if (!$this->ts()) {
                $this->expired = false;
            } else {
                $this->expired = null;
                foreach ($this->maintenance->getLogs() as $id => $log) {
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
     * @return    string    Task ID (class name)
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get task name.
     *
     * @return    string    Task name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get task description.
     *
     * @return    null|string    Description
     */
    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * Get task tab.
     *
     * @return    null|string    Task tab ID or null
     */
    public function tab(): ?string
    {
        return $this->tab;
    }

    /**
     * Get task group.
     *
     * If task required a full tab, this must be returned null.
     *
     * @return    null|string    Task group ID or null
     */
    public function group(): ?string
    {
        return $this->group;
    }

    /**
     * Use ajax
     *
     * Is task use maintenance ajax script for steps process.
     *
     * @return    boolean    Use ajax
     */
    public function ajax(): bool
    {
        return (bool) $this->ajax;
    }

    /**
     * Get task message.
     *
     * This message is used on form button.
     *
     * @return    string    Message
     */
    public function task(): string
    {
        return $this->task;
    }

    /**
     * Get step message.
     *
     * This message is displayed during task step execution.
     *
     * @return    mixed     Message or null
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
     * @return    string    Message or null
     */
    public function success(): string
    {
        return $this->success;
    }

    /**
     * Get error message.
     *
     * This message is displayed on error.
     *
     * @return    string    Message or null
     */
    public function error(): string
    {
        return $this->error;
    }

    /**
     * Get header.
     *
     * Headers required on maintenance page.
     *
     * @return     null|string    Message or null
     */
    public function header(): ?string
    {
        return '';
    }

    /**
     * Get content.
     *
     * Content for full tab task.
     *
     * @return    null|string    Tab's content
     */
    public function content(): ?string
    {
        return '';
    }

    /**
     * Execute task.
     *
     * @return    bool|int
     *    - FALSE on error,
     *    - TRUE if task is finished
     *    - INT if task required a next step
     */
    public function execute()
    {
        return true;
    }

    /**
     * Log task execution.
     *
     * Sometimes we need to log task execution
     * direct from task itself.
     */
    protected function log(): void
    {
        $this->maintenance->setLog($this->id);
    }

    /**
     * Help function.
     */
    public function help()
    {
    }
}
