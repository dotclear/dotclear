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

use dcCore;
use dcBlog;
use Dotclear\Core\Core;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Plugin\maintenance\MaintenanceTask;

class CountComments extends MaintenanceTask
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
            ->ref($sql_com->alias(Core::con()->prefix() . dcBlog::POST_TABLE_NAME, 'P'));

        $sql_tb = clone $sql_com;

        $sql_count_com = new SelectStatement();
        $sql_count_com
            ->field($sql_count_com->count('C.comment_id'))
            ->from($sql_count_com->alias(Core::con()->prefix() . dcBlog::COMMENT_TABLE_NAME, 'C'))
            ->where('C.post_id = P.post_id')
            ->and('C.comment_status = ' . (string) dcBlog::COMMENT_PUBLISHED);

        $sql_count_tb = clone $sql_count_com;

        $sql_count_com->and('C.comment_trackback <> 1');    // Count comment only
        $sql_count_tb->and('C.comment_trackback = 1');      // Count trackback only

        $sql_com->set('nb_comment = (' . $sql_count_com->statement() . ')');
        $sql_com->update();

        $sql_tb->set('nb_trackback = (' . $sql_count_tb->statement() . ')');
        $sql_tb->update();
    }
}
