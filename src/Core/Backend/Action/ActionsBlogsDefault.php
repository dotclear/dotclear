<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Action;

use dcBlog;
use dcCore;
use Dotclear\Core\Backend\Page;
use Exception;

class ActionsBlogsDefault
{
    /**
     * Set blog actions
     *
     * @param      ActionsBlogs  $ap     { parameter_description }
     */
    public static function adminBlogsActionsPage(ActionsBlogs $ap)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            return;
        }

        $ap->addAction(
            [__('Status') => [
                __('Set online')     => 'online',
                __('Set offline')    => 'offline',
                __('Set as removed') => 'remove',
            ]],
            [self::class, 'doChangeBlogStatus']
        );
        $ap->addAction(
            [__('Delete') => [
                __('Delete') => 'delete', ]],
            [self::class, 'doDeleteBlog']
        );
    }

    /**
     * Does a change blog status.
     *
     * @param      ActionsBlogs  $ap
     *
     * @throws     Exception             If no blog selected
     */
    public static function doChangeBlogStatus(ActionsBlogs $ap)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            return;
        }

        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No blog selected'));
        }

        switch ($ap->getAction()) {
            case 'online':
                $status = 1;

                break;
            case 'offline':
                $status = 0;

                break;
            case 'remove':
                $status = -1;

                break;
            default:
                $status = 1;

                break;
        }

        $cur              = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::BLOG_TABLE_NAME);
        $cur->blog_status = $status;
        $cur->update('WHERE blog_id ' . dcCore::app()->con->in($ids));

        if ($status === dcBlog::BLOG_REMOVED) {
            // Remove these blogs from user default blog
            dcCore::app()->removeUsersDefaultBlogs($ids);
        }

        Page::addSuccessNotice(__('Selected blogs have been successfully updated.'));
        $ap->redirect(true);
    }

    /**
     * Does a delete blog.
     *
     * @param      ActionsBlogs  $ap
     *
     * @throws     Exception             If no blog selected
     */
    public static function doDeleteBlog(ActionsBlogs $ap)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            return;
        }

        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No blog selected'));
        }

        if (!dcCore::app()->auth->checkPassword($_POST['pwd'])) {
            throw new Exception(__('Password verification failed'));
        }

        $checked_ids = [];
        foreach ($ids as $id) {
            if ($id === dcCore::app()->blog->id) {
                Page::addWarningNotice(__('The current blog cannot be deleted.'));
            } else {
                $checked_ids[] = $id;
            }
        }

        if (!empty($checked_ids)) {
            # --BEHAVIOR-- adminBeforeBlogsDelete -- array<int,string>
            dcCore::app()->callBehavior('adminBeforeBlogsDelete', $checked_ids);

            foreach ($checked_ids as $id) {
                dcCore::app()->delBlog($id);
            }

            Page::addSuccessNotice(
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
