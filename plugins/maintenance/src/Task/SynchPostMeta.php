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

use dcBlog;
use dcCore;
use dcMeta;
use Dotclear\Core\Core;
use Dotclear\Database\MetaRecord;
use Dotclear\Plugin\maintenance\MaintenanceTask;

class SynchPostsMeta extends MaintenanceTask
{
    protected $id = 'dcMaintenanceSynchpostsmeta';

    /**
     * Task use AJAX
     *
     * @var bool
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
    protected $limit = 100;

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
        $this->name      = __('Entries metadata');
        $this->task      = __('Synchronize entries metadata');
        $this->step_task = __('Next');
        $this->step      = __('Synchronize entry %d to %d.');
        $this->success   = __('Entries metadata synchronize done.');
        $this->error     = __('Failed to synchronize entries metadata.');

        $this->description = __('Synchronize all entries metadata could be useful after importing content in your blog or do bad operation on database tables.');
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
        $this->code = $this->synchronizeAllPostsmeta($this->code, $this->limit);

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

    /**
     * Synchronize posts meta
     *
     * @param      int|null  $start  The start
     * @param      int|null  $limit  The limit
     *
     * @return     int|null     Next offset if any
     */
    protected function synchronizeAllPostsmeta(?int $start = null, ?int $limit = null): ?int
    {
        // Get number of posts
        $rs    = new MetaRecord(Core::con()->select('SELECT COUNT(post_id) FROM ' . Core::con()->prefix() . dcBlog::POST_TABLE_NAME));
        $count = $rs->f(0);

        // Get posts ids to update
        $req_limit = $start !== null && $limit !== null ? Core::con()->limit($start, $limit) : '';
        $rs        = new MetaRecord(Core::con()->select('SELECT post_id FROM ' . Core::con()->prefix() . dcBlog::POST_TABLE_NAME . ' ' . $req_limit));

        // Update posts meta
        while ($rs->fetch()) {
            $rs_meta = new MetaRecord(Core::con()->select('SELECT meta_id, meta_type FROM ' . Core::con()->prefix() . dcMeta::META_TABLE_NAME . ' WHERE post_id = ' . $rs->post_id . ' '));

            $meta = [];
            while ($rs_meta->fetch()) {
                $meta[$rs_meta->meta_type][] = $rs_meta->meta_id;
            }

            $cur            = Core::con()->openCursor(Core::con()->prefix() . dcBlog::POST_TABLE_NAME);
            $cur->post_meta = serialize($meta);
            $cur->update('WHERE post_id = ' . $rs->post_id);
        }
        Core::blog()->triggerBlog();

        // Return next step
        return $start + $limit > $count ? null : $start + $limit;
    }
}
