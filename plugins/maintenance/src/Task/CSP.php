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

use Dotclear\Plugin\maintenance\MaintenanceTask;
use path;

class CSP extends MaintenanceTask
{
    protected $id = 'dcMaintenanceCSP';

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
        $this->task    = __('Delete the Content-Security-Policy report file');
        $this->success = __('Content-Security-Policy report file has been deleted.');
        $this->error   = __('Failed to delete the Content-Security-Policy report file.');

        $this->description = __('Remove the Content-Security-Policy report file.');
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
        $csp_file = path::real(DC_VAR) . '/csp/csp_report.json';
        if (file_exists($csp_file)) {
            unlink($csp_file);
        }

        return true;
    }
}
