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
 * @brief   The comments index maintenance task.
 * @ingroup maintenance
 */
class IndexComments extends MaintenanceTask
{
    /**
     * Task ID (class name).
     */
    protected ?string $id = 'dcMaintenanceIndexcomments';

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
        $this->task      = __('Index all comments for search engine');
        $this->step_task = __('Next');
        $this->step      = __('Indexing comment %d to %d.');
        $this->success   = __('Comments index done.');
        $this->error     = __('Failed to index comments.');

        $this->description = __('Index all comments and trackbacks in search engine index. This operation is necessary, after importing content in your blog, to use internal search engine, on public and private pages.');
    }

    public function execute(): bool|int
    {
        $this->code = $this->indexAllComments((int) $this->code, $this->limit);

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
     * Recreates comments search engine index.
     *
     * @param   null|int    $start  The start comment index
     * @param   null|int    $limit  The limit of comment to index
     *
     * @return  null|int    Sum of <var>$start</var> and <var>$limit</var>
     */
    public function indexAllComments(?int $start = null, ?int $limit = null): ?int
    {
        $sql = new SelectStatement();
        $run = $sql
            ->column($sql->count('comment_id'))
            ->from(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME)
            ->select();
        $count = $run instanceof MetaRecord ? $run->f(0) : 0;

        $sql = new SelectStatement();
        $sql
            ->columns([
                'comment_id',
                'comment_content',
            ])
            ->from(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME);

        if ($start !== null && $limit !== null) {
            $sql->limit([$start, $limit]);
        }

        $rs = $sql->select();
        if ($rs instanceof MetaRecord) {
            $cur = App::blog()->openCommentCursor();

            while ($rs->fetch()) {
                $cur->comment_words = implode(' ', Text::splitWords($rs->comment_content));
                $cur->update('WHERE comment_id = ' . (int) $rs->comment_id);
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
