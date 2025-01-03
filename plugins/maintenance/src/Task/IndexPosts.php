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
use Dotclear\Helper\Text;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The posts index maintenance task.
 * @ingroup maintenance
 */
class IndexPosts extends MaintenanceTask
{
    /**
     * Task ID (class name).
     */
    protected ?string $id = 'dcMaintenanceIndexposts';

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
    protected int $limit = 500;

    /**
     * Next step label.
     */
    protected string $step_task;

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

    public function execute(): bool|int
    {
        $this->code = $this->indexAllPosts((int) $this->code, $this->limit);

        return $this->code ?: true;
    }

    public function task(): string
    {
        return $this->code ? $this->step_task : $this->task;
    }

    public function step(): ?string
    {
        return $this->code ? sprintf((string) $this->step, $this->code - $this->limit, $this->code) : null;
    }

    public function success(): string
    {
        return $this->code ? sprintf((string) $this->step, $this->code - $this->limit, $this->code) : $this->success;
    }

    /**
     * Recreates entries search engine index.
     *
     * @param   null|int    $start  The start entry index
     * @param   null|int    $limit  The limit of entry to index
     *
     * @return  null|int    Sum of <var>$start</var> and <var>$limit</var>
     */
    public function indexAllPosts(?int $start = null, ?int $limit = null): ?int
    {
        $sql = new SelectStatement();
        $run = $sql
            ->column($sql->count('post_id'))
            ->from(App::con()->prefix() . App::blog()::POST_TABLE_NAME)
            ->select();
        $count = $run instanceof MetaRecord ? $run->f(0) : 0;

        $sql = new SelectStatement();
        $sql
            ->columns([
                'post_id',
                'post_title',
                'post_excerpt_xhtml',
                'post_content_xhtml',
            ])
            ->from(App::con()->prefix() . App::blog()::POST_TABLE_NAME);

        if ($start !== null && $limit !== null) {
            $sql->limit([$start, $limit]);
        }

        $rs = $sql->select();
        if ($rs instanceof MetaRecord) {
            $cur = App::blog()->openPostCursor();

            while ($rs->fetch()) {
                $words = $rs->post_title . ' ' . $rs->post_excerpt_xhtml . ' ' .
                $rs->post_content_xhtml;

                $cur->post_words = implode(' ', Text::splitWords($words));
                $cur->update('WHERE post_id = ' . (int) $rs->post_id);
                $cur->clean();
            }
        }

        $start = (int) $start;
        $limit = (int) $limit;

        if ($start + $limit > $count) {
            return null;
        }

        return $start + $limit;
    }
}
