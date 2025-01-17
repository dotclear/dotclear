<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsPostsDefault;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\Statement\UpdateStatement;
use Exception;

/**
 * @brief   The module backend default pages actions.
 * @ingroup pages
 */
class BackendDefaultActions
{
    /**
     * Set pages actions.
     *
     * @param   BackendActions  $ap     Admin actions instance
     */
    public static function adminPagesActionsPage(BackendActions $ap): void
    {
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Status') => App::status()->post()->action()],
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
     * @param   BackendActions                  $ap     Admin actions instance
     * @param   ArrayObject<string, mixed>      $post   The post
     *
     * @throws  Exception   If user permission not granted
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

            $cur                = App::blog()->openPostCursor();
            $cur->post_position = (int) $value - 1;
            $cur->post_upddt    = date('Y-m-d H:i:s');

            $sql = new UpdateStatement();
            $sql
                ->where('blog_id = ' . $sql->quote(App::blog()->id()))
                ->and('post_id ' . $sql->in($post_id));

            #If user can only publish, we need to check the post's owner
            if (!App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())) {
                $sql->and('user_id = ' . $sql->quote((string) App::auth()->userID()));
            }

            $sql->update($cur);

            App::blog()->triggerBlog();
        }

        Notices::addSuccessNotice(__('Selected pages have been successfully reordered.'));
        $ap->redirect(false);
    }
}
