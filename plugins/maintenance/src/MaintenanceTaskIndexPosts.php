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

class MaintenanceTaskIndexPosts extends MaintenanceTask
{
    protected $id = 'dcMaintenanceIndexposts';

    /**
     * Use ajax
     *
     * Is task use maintenance ajax script for steps process.
     *
     * @return    boolean    Use ajax
     */
    protected $ajax = true;

    /**
     * Task group container
     *
     * @var string
     */
    protected $group = 'index';

    /**
     * Number of entries to process by step
     *
     * @var int
     */
    protected $limit = 500;

    /**
     * Next step label
     *
     * @var string
     */
    protected $step_task;

    /**
     * Initialize task object.
     */
    protected function init(): void
    {
        $this->name      = __('Search engine index');
        $this->task      = __('Index all entries for search engine');
        $this->step_task = __('Next');
        $this->step      = __('Indexing entry %d to %d.');
        $this->success   = __('Entries index done.');
        $this->error     = __('Failed to index entries.');

        $this->description = __('Index all entries in search engine index. This operation is necessary, after importing content in your blog, to use internal search engine, on public and private pages.');
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
        $this->code = dcCore::app()->indexAllPosts($this->code, $this->limit);

        return $this->code ?: true;
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
        return $this->code ? $this->step_task : $this->task;
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
        return $this->code ? sprintf($this->step, $this->code - $this->limit, $this->code) : null;
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
        return $this->code ? sprintf($this->step, $this->code - $this->limit, $this->code) : $this->success;
    }
}
