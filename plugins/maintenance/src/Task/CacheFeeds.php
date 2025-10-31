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
use Dotclear\Helper\Network\Feed\Reader;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The feeds cache maintenance task.
 * @ingroup maintenance
 */
class CacheFeeds extends MaintenanceTask
{
    /**
     * Task ID (class name).
     */
    protected ?string $id = 'dcMaintenanceCacheFeeds';

    /**
     * Task group container.
     */
    protected string $group = 'purge';

    /**
     * Initialize task object.
     */
    protected function init(): void
    {
        $this->task    = __('Empty the feed cache directory');
        $this->success = __('The feed cache directory has been emptied.');
        $this->error   = __('Failed to delete the feed cache directory.');

        $this->description = sprintf(__('Notice : with some hosters, the feed cache cannot be emptied with this plugin. You may then have to delete the directory <strong>%s</strong> directly on the server with your FTP software.'), DIRECTORY_SEPARATOR . Reader::CACHE_FOLDER . DIRECTORY_SEPARATOR);
    }

    public function execute(): bool|int
    {
        App::cache()->emptyFeedsCache();

        return true;
    }
}
