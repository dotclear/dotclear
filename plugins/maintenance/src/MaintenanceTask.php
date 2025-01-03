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

/**
 * @brief   The maintenance task.
 * @ingroup maintenance
 *
 * Every task of maintenance must extend this class.
 */
class MaintenanceTask
{
    /**
     * Task code.
     */
    protected ?int $code = null;

    /**
     * Task timestamp.
     *
     * @var     bool|int    $ts
     */
    protected mixed $ts = 0;

    /**
     * Expired flag.
     *
     * @var     null|int|bool   $expired
     */
    protected mixed $expired = 0;

    /**
     * Task use AJAX.
     */
    protected bool $ajax = false;

    /**
     * Task limited to current blog.
     */
    protected bool $blog = false;

    /**
     * Task permissions.
     */
    protected ?string $perm = null;

    /**
     * Task ID (class name).
     */
    protected ?string $id = null;

    /**
     * Task name.
     */
    protected string $name = '';

    /**
     * Task description.
     */
    protected string $description = '';

    /**
     * Task tab container.
     */
    protected string $tab = 'maintenance';

    /**
     * Task group container.
     */
    protected string $group = 'other';

    /**
     * Task message.
     */
    protected string $task = '';

    /**
     * Task step.
     */
    protected ?string $step = null;

    /**
     * Task error message.
     */
    protected string $error = '';

    /**
     * Task success message.
     */
    protected string $success = '';

    /**
     * Constructor.
     *
     * If your task required something on construct,
     * use method init() to do it.
     *
     * @param   Maintenance     $maintenance    The maintenance
     */
    public function __construct(
        protected Maintenance $maintenance
    ) {
        $this->init();

        if (!preg_match('/^\w+$/', (string) $this->id)) {
            // Set id if not yet defined
            $path     = explode('\\', static::class);
            $this->id = array_pop($path);
        }

        if ($this->perm() === null && !App::auth()->isSuperAdmin()
            || !App::auth()->check($this->perm(), App::blog()->id())) {
            return;
        }

        if ($this->name === '') {
            $this->name = (string) $this->id;
        }
        if ($this->error === '') {
            $this->error = __('Failed to execute task.');
        }
        if ($this->success === '') {
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
     * @return  null|string     Permission.
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
     * @return  bool    Limit to blog
     */
    public function blog(): bool
    {
        return $this->blog;
    }

    /**
     * Set $code for task having multiple steps.
     *
     * @param   int|null     $code   Code used for task execution
     */
    public function code(?int $code): void
    {
        $this->code = $code;
    }

    /**
     * Get timestamp between maintenances.
     *
     * @return  false|int   Timestamp
     */
    public function ts(): int|false
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
     * @return  null|bool|int   Last update
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
     * @return  string  Task ID (class name)
     */
    public function id(): string
    {
        return (string) $this->id;
    }

    /**
     * Get task name.
     *
     * @return  string  Task name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get task description.
     *
     * @return  null|string     Description
     */
    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * Get task tab.
     *
     * @return  null|string     Task tab ID or null
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
     * @return  null|string     Task group ID or null
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
     * @return  bool    Use ajax
     */
    public function ajax(): bool
    {
        return $this->ajax;
    }

    /**
     * Get task message.
     *
     * This message is used on form button.
     *
     * @return  string   Message
     */
    public function task(): string
    {
        return $this->task;
    }

    /**
     * Get step message.
     *
     * This message is displayed during task step execution.
     */
    public function step(): ?string
    {
        return $this->step;
    }

    /**
     * Get success message.
     *
     * This message is displayed when task is accomplished.
     *
     * @return  string  Message or null
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
     * @return  string  Message or null
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
     * @return  null|string     Message or null
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
     * @return  null|string     Tab's content
     */
    public function content(): ?string
    {
        return '';
    }

    /**
     * Execute task.
     *
     * @return  bool|int
     *    - FALSE on error,
     *    - TRUE if task is finished
     *    - INT if task required a next step
     */
    public function execute(): bool|int
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
        $this->maintenance->setLog((string) $this->id);
    }

    /**
     * Help function.
     */
    public function help(): void
    {
    }
}
