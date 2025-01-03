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
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The comments count maintenance task.
 * @ingroup maintenance
 */
class CountComments extends MaintenanceTask
{
    /**
     * Task ID (class name).
     */
    protected ?string $id = 'dcMaintenanceCountcomments';

    /**
     * Task group container.
     */
    protected string $group = 'index';

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

    public function execute(): bool|int
    {
        $this->countAllComments();

        return true;
    }

    /**
     * Reinits nb_comment and nb_trackback in post table.
     */
    public function countAllComments(): void
    {
        $sql_com = new UpdateStatement();
        $sql_com
            ->ref($sql_com->alias(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'));

        $sql_tb = clone $sql_com;

        $sql_count_com = new SelectStatement();
        $sql_count_com
            ->field($sql_count_com->count('C.comment_id'))
            ->from($sql_count_com->alias(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->where('C.post_id = P.post_id')
            ->and('C.comment_status = ' . App::blog()::COMMENT_PUBLISHED);

        $sql_count_tb = clone $sql_count_com;

        $sql_count_com->and('C.comment_trackback <> 1');    // Count comment only
        $sql_count_tb->and('C.comment_trackback = 1');      // Count trackback only

        $sql_com->set('nb_comment = (' . $sql_count_com->statement() . ')');
        $sql_com->update();

        $sql_tb->set('nb_trackback = (' . $sql_count_tb->statement() . ')');
        $sql_tb->update();
    }
}
