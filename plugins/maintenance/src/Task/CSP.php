<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance\Task;

use Dotclear\App;
use Dotclear\Helper\File\Path;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The CSP maintenance task.
 * @ingroup maintenance
 */
class CSP extends MaintenanceTask
{
    /**
     * Task ID (class name).
     *
     * @var     null|string     $id
     */
    protected $id = 'dcMaintenanceCSP';

    /**
     * Task group container.
     *
     * @var     string  $group
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

    public function execute()
    {
        $csp_file = Path::real(App::config()->varRoot()) . '/csp/csp_report.json';
        if (file_exists($csp_file)) {
            unlink($csp_file);
        }

        return true;
    }
}
