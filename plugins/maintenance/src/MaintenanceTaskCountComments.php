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

namespace Dotclear\Plugin\maintenance;

use dcCore;

class MaintenanceTaskCountComments extends MaintenanceTask
{
    protected $id = 'dcMaintenanceCountcomments';

    /**
     * Task group container
     *
     * @var string
     */
    protected $group = 'index';

    /**
     * Initialize task object.
     */
    protected function init(): void
    {
        $this->task    = __('Count again comments and trackbacks');
        $this->success = __('Comments and trackback counted.');
        $this->error   = __('Failed to count comments and trackbacks.');

        $this->description = __('Count again comments and trackbacks allows to check their exact numbers. This operation can be useful when importing from another blog platform (or when migrating from dotclear 1 to dotclear 2).');
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
        dcCore::app()->countAllComments();

        return true;
    }
}
