<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Action;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\Statement\UpdateStatement;
use Exception;

/**
 * @brief   Handler for default actions on blogs.
 */
class ActionsBlogsDefault
{
    /**
     * Set blog actions.
     *
     * @param   ActionsBlogs    $ap     The ActionsBlogs instance
     */
    public static function adminBlogsActionsPage(ActionsBlogs $ap): void
    {
        if (!App::auth()->isSuperAdmin()) {
            return;
        }

        $ap->addAction(
            [__('Status') => App::status()->blog()->action()],
            self::doChangeBlogStatus(...)
        );
        $ap->addAction(
            [__('Delete') => [
                __('Delete') => 'delete', ]],
            self::doDeleteBlog(...)
        );
    }

    /**
     * Does a change blog status.
     *
     * @param   ActionsBlogs    $ap     The ActionsBlogs instance
     *
     * @throws  Exception   If no blog selected
     */
    public static function doChangeBlogStatus(ActionsBlogs $ap): void
    {
        if (!App::auth()->isSuperAdmin()) {
            return;
        }

        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No blog selected'));
        }

        // unknown to online
        $status = App::status()->blog()->has((string) $ap->getAction()) ?
            App::status()->blog()->level((string) $ap->getAction()) :
            App::status()->blog()::ONLINE;

        $cur              = App::blog()->openBlogCursor();
        $cur->blog_status = $status;

        $sql = new UpdateStatement();
        $sql
            ->where('blog_id ' . $sql->in($ids))
            ->update($cur);

        if ($status === App::status()->blog()::REMOVED) {
            // Remove these blogs from user default blog
            App::users()->removeUsersDefaultBlogs($ids);
        }

        Notices::addSuccessNotice(__('Selected blogs have been successfully updated.'));
        $ap->redirect(true);
    }

    /**
     * Does a delete blog.
     *
     * @param   ActionsBlogs    $ap     The ActionsBlogs instance
     *
     * @throws  Exception   If no blog selected
     */
    public static function doDeleteBlog(ActionsBlogs $ap): void
    {
        if (!App::auth()->isSuperAdmin()) {
            return;
        }

        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No blog selected'));
        }

        if (!App::auth()->checkPassword($_POST['pwd'])) {
            throw new Exception(__('Password verification failed'));
        }

        $checked_ids = [];
        foreach ($ids as $id) {
            if ($id === App::blog()->id()) {
                Notices::addWarningNotice(__('The current blog cannot be deleted.'));
            } else {
                $checked_ids[] = $id;
            }
        }

        if ($checked_ids !== []) {
            # --BEHAVIOR-- adminBeforeBlogsDelete -- array<int,string>
            App::behavior()->callBehavior('adminBeforeBlogsDelete', $checked_ids);

            foreach ($checked_ids as $id) {
                App::blogs()->delBlog($id);
            }

            Notices::addSuccessNotice(
                sprintf(
                    __(
                        '%d blog has been successfully deleted',
                        '%d blogs have been successfully deleted',
                        count($checked_ids)
                    ),
                    count($checked_ids)
                )
            );
        }
        $ap->redirect(false);
    }
}
