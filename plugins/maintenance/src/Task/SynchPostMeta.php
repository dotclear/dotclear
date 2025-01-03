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
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The post meta synch maintenance task.
 * @ingroup maintenance
 */
class SynchPostsMeta extends MaintenanceTask
{
    /**
     * Task ID (class name).
     */
    protected ?string $id = 'dcMaintenanceSynchpostsmeta';

    /**
     * Task use AJAX.
     */
    protected bool $ajax = true;

    /**
     * Task group container.
     */
    protected string $group = 'index';

    /**
     * Number of comments to process by step.
     */
    protected int $limit = 100;

    /**
     * Next step label.
     */
    protected string $step_task;

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

    public function execute(): bool|int
    {
        $this->code = $this->synchronizeAllPostsmeta($this->code, $this->limit);

        return $this->code ?: true;
    }

    public function task(): string
    {
        return $this->code ? $this->step_task : $this->task;
    }

    public function step(): ?string
    {
        return $this->code ? sprintf($this->step ?? '', $this->code - $this->limit, $this->code) : null;
    }

    public function success(): string
    {
        return $this->code ? sprintf($this->step ?? '', $this->code - $this->limit, $this->code) : $this->success;
    }

    /**
     * Synchronize posts meta.
     *
     * @param   int|null    $start  The start
     * @param   int|null    $limit  The limit
     *
     * @return  int|null    Next offset if any
     */
    protected function synchronizeAllPostsmeta(?int $start = null, ?int $limit = null): ?int
    {
        // Get number of posts
        $sql = new SelectStatement();
        $run = $sql
            ->column($sql->count('post_id'))
            ->from(App::con()->prefix() . App::blog()::POST_TABLE_NAME)
            ->select();
        $count = $run instanceof MetaRecord ? $run->f(0) : 0;

        // Get posts ids to update
        $sql = new SelectStatement();
        $sql
            ->column('post_id')
            ->from(App::con()->prefix() . App::blog()::POST_TABLE_NAME);
        if ($start !== null && $limit !== null) {
            $sql->limit([$start, $limit]);
        }

        // Update posts meta
        $rs = $sql->select();
        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                $sql_meta = new SelectStatement();
                $rs_meta  = $sql_meta
                    ->columns([
                        'meta_id',
                        'meta_type',
                    ])
                    ->from(App::con()->prefix() . App::meta()::META_TABLE_NAME)
                    ->where('post_id = ' . $rs->post_id)
                    ->select();

                $meta = [];
                if ($rs_meta instanceof MetaRecord) {
                    while ($rs_meta->fetch()) {
                        $meta[$rs_meta->meta_type][] = $rs_meta->meta_id;
                    }
                }

                $cur            = App::blog()->openPostCursor();
                $cur->post_meta = serialize($meta);

                $sql_upd = new UpdateStatement();
                $sql_upd
                    ->where('post_id = ' . $rs->post_id)
                    ->update($cur);
            }
        }
        App::blog()->triggerBlog();

        // Return next step
        return $start + $limit > $count ? null : $start + $limit;
    }
}
