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
use Dotclear\App;
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
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Status') => [
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Schedule')        => 'schedule',
                    __('Mark as pending') => 'pending',
                ]],
                ActionsPostsDefault::doChangePostStatus(...)
            );
        }
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('First publication') => [
                    __('Never published')   => 'never',
                    __('Already published') => 'already',
                ]],
                ActionsPostsDefault::doChangePostFirstPub(...)
            );
        }
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Change') => [
                    __('Change author') => 'author', ]],
                ActionsPostsDefault::doChangePostAuthor(...)
            );
        }
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                ActionsPostsDefault::doDeletePost(...)
            );
        }
        $ap->addAction(
            [__('Order') => [
                __('Save order') => 'reorder', ]],
            self::doReorderPages(...)
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
            if (!App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_PUBLISH,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())) {
                throw new Exception(__('You are not allowed to change this entry status'));
            }

            $strReq = "WHERE blog_id = '" . App::con()->escape(App::blog()->id()) . "' " .
            'AND post_id ' . App::con()->in($post_id);

            #If user can only publish, we need to check the post's owner
            if (!App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())) {
                $strReq .= "AND user_id = '" . App::con()->escape(App::auth()->userID()) . "' ";
            }

            $cur = App::blog()->openPostCursor();

            $cur->post_position = (int) $value - 1;
            $cur->post_upddt    = date('Y-m-d H:i:s');

            $cur->update($strReq);
            App::blog()->triggerBlog();
        }

        Notices::addSuccessNotice(__('Selected pages have been successfully reordered.'));
        $ap->redirect(false);
    }
}
