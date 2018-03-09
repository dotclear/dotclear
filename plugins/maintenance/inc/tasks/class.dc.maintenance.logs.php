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

class dcMaintenanceLogs extends dcMaintenanceTask
{
    public static $keep_maintenance_logs = true;

    protected $group = 'purge';

    protected function init()
    {
        $this->task    = __('Delete all logs');
        $this->success = __('Logs deleted.');
        $this->error   = __('Failed to delete logs.');

        $this->description = __('Logs record all activity and connection to your blog history. Unless you need to keep this history, consider deleting these logs from time to time.');
    }

    public function execute()
    {
        if (dcMaintenanceLogs::$keep_maintenance_logs) {
            $this->core->con->execute(
                'DELETE FROM ' . $this->core->prefix . 'log ' .
                "WHERE log_table <> 'maintenance' "
            );
        } else {
            $this->core->log->delLogs(null, true);
        }

        return true;
    }
}
