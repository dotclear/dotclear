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

use Dotclear\Core\Utils;
use Dotclear\Helper\Html\Template\Template;
use Dotclear\Plugin\maintenance\MaintenanceTask;

class Cache extends MaintenanceTask
{
    protected $id = 'dcMaintenanceCache';

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
        $this->task    = __('Empty templates cache directory');
        $this->success = __('Templates cache directory emptied.');
        $this->error   = __('Failed to empty templates cache directory.');

        $this->description = sprintf(__("It may be useful to empty this cache when modifying a theme's .html or .css files (or when updating a theme or plugin). Notice : with some hosters, the templates cache cannot be emptied with this plugin. You may then have to delete the directory <strong>%s</strong> directly on the server with your FTP software."), DIRECTORY_SEPARATOR . Template::CACHE_FOLDER . DIRECTORY_SEPARATOR);
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
        Utils::emptyTemplatesCache();

        return true;
    }
}
