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
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance\Task;

use dcCore;
use dcLog;
use Dotclear\Core\Core;
use Dotclear\Plugin\maintenance\MaintenanceTask;

class Logs extends MaintenanceTask
{
    protected $id = 'dcMaintenanceLogs';

    /**
     * Keep maintenance logs?
     *
     * @var        bool
     */
    public static $keep_maintenance_logs = true;

    /**
     * Task group container
     *
     * @var string
     */
    protected $group = 'purge';

    /**
     * Initialize task object.
     */
    protected function init(): void
    {
        $this->task    = __('Delete all logs');
        $this->success = __('Logs deleted.');
        $this->error   = __('Failed to delete logs.');

        $this->description = __('Logs record all activity and connection to your blog history. Unless you need to keep this history, consider deleting these logs from time to time.');
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
        if (static::$keep_maintenance_logs) {
            Core::con()->execute(
                'DELETE FROM ' . Core::con()->prefix() . dcLog::LOG_TABLE_NAME . ' ' .
                "WHERE log_table <> 'maintenance' "
            );
        } else {
            Core::log()->delLogs(null, true);
        }

        return true;
    }
}
