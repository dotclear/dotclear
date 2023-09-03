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

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
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
        if (!App::auth()->isSuperAdmin()) {
            return;
        }

        $ap->addAction(
            [__('Status') => [
                __('Set online')     => 'online',
                __('Set offline')    => 'offline',
                __('Set as removed') => 'remove',
            ]],
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
     * @param      ActionsBlogs  $ap
     *
     * @throws     Exception             If no blog selected
     */
    public static function doChangeBlogStatus(ActionsBlogs $ap)
    {
        if (!App::auth()->isSuperAdmin()) {
            return;
        }

        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No blog selected'));
        }

        $status = match ($ap->getAction()) {
            'offline' => App::blog()::BLOG_OFFLINE,
            'remove'  => App::blog()::BLOG_REMOVED,
            default   => App::blog()::BLOG_ONLINE,
        };

        $cur              = App::blog()->openBlogCursor();
        $cur->blog_status = $status;
        $cur->update('WHERE blog_id ' . App::con()->in($ids));

        if ($status === App::blog()::BLOG_REMOVED) {
            // Remove these blogs from user default blog
            App::users()->removeUsersDefaultBlogs($ids);
        }

        Notices::addSuccessNotice(__('Selected blogs have been successfully updated.'));
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
        if (!App::auth()->isSuperAdmin()) {
            return;
        }

        $ids = $ap->getIDs();
        if (empty($ids)) {
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

        if (!empty($checked_ids)) {
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
