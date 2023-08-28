<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use dcBlog;
use dcCore;
use Dotclear\Core\Core;
use Dotclear\Core\Backend\Action\ActionsPostsDefault;
use Dotclear\Core\Backend\Notices;
use Exception;

class BackendDefaultActions
{
    /**
     * Set pages actions
     *
     * @param      BackendActions  $ap     Admin actions instance
     */
    public static function adminPagesActionsPage(BackendActions $ap): void
    {
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_PUBLISH,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id)) {
            $ap->addAction(
                [__('Status') => [
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Schedule')        => 'schedule',
                    __('Mark as pending') => 'pending',
                ]],
                [ActionsPostsDefault::class, 'doChangePostStatus']
            );
        }
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_PUBLISH,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id)) {
            $ap->addAction(
                [__('First publication') => [
                    __('Never published')   => 'never',
                    __('Already published') => 'already',
                ]],
                [ActionsPostsDefault::class, 'doChangePostFirstPub']
            );
        }
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_ADMIN,
        ]), Core::blog()->id)) {
            $ap->addAction(
                [__('Change') => [
                    __('Change author') => 'author', ]],
                [ActionsPostsDefault::class, 'doChangePostAuthor']
            );
        }
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_DELETE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id)) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                [ActionsPostsDefault::class, 'doDeletePost']
            );
        }
        $ap->addAction(
            [__('Order') => [
                __('Save order') => 'reorder', ]],
            [self::class, 'doReorderPages']
        );
    }

    /**
     * Does reorder pages.
     *
     * @param      BackendActions  $ap  Admin actions instance
     * @param      ArrayObject     $post   The post
     *
     * @throws     Exception             If user permission not granted
     */
    public static function doReorderPages(BackendActions $ap, ArrayObject $post): void
    {
        foreach ($post['order'] as $post_id => $value) {
            if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_PUBLISH,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), Core::blog()->id)) {
                throw new Exception(__('You are not allowed to change this entry status'));
            }

            $strReq = "WHERE blog_id = '" . Core::con()->escape(Core::blog()->id) . "' " .
            'AND post_id ' . Core::con()->in($post_id);

            #If user can only publish, we need to check the post's owner
            if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), Core::blog()->id)) {
                $strReq .= "AND user_id = '" . Core::con()->escape(dcCore::app()->auth->userID()) . "' ";
            }

            $cur = Core::con()->openCursor(Core::con()->prefix() . dcBlog::POST_TABLE_NAME);

            $cur->post_position = (int) $value - 1;
            $cur->post_upddt    = date('Y-m-d H:i:s');

            $cur->update($strReq);
            Core::blog()->triggerBlog();
        }

        Notices::addSuccessNotice(__('Selected pages have been successfully reordered.'));
        $ap->redirect(false);
    }
}
