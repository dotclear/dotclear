<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance\Task;

use Dotclear\App;
use Dotclear\Helper\Html\Template\Template;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The cache maintenance task.
 * @ingroup maintenance
 */
class Cache extends MaintenanceTask
{
    /**
     * Task ID (class name).
     */
    protected ?string $id = 'dcMaintenanceCache';

    /**
     * Task group container.
     */
    protected string $group = 'purge';

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

    public function execute(): bool|int
    {
        App::cache()->emptyTemplatesCache();

        return true;
    }
}
