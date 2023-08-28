<?php
/**
 * @since 2.27 Before as admin/posts.php
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcCore;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Filter\FilterPosts;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Posts extends Process
{
    public static function init(): bool
    {
        Page::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]));

        // Actions
        // -------
        dcCore::app()->admin->posts_actions_page = new ActionsPosts(dcCore::app()->admin->url->get('admin.posts'));
        if (dcCore::app()->admin->posts_actions_page->process()) {
            return self::status(false);
        }

        // Filters
        // -------
        dcCore::app()->admin->post_filter = new FilterPosts();

        // get list params
        $params = dcCore::app()->admin->post_filter->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'post_title' => 'post_title',
            'cat_title'  => 'cat_title',
            'user_id'    => 'P.user_id', ];

        # --BEHAVIOR-- adminPostsSortbyLexCombo -- array<int,array<string,string>>
        Core::behavior()->callBehavior('adminPostsSortbyLexCombo', [& $sortby_lex]);

        $params['order'] = (array_key_exists(dcCore::app()->admin->post_filter->sortby, $sortby_lex) ?
            dcCore::app()->con->lexFields($sortby_lex[dcCore::app()->admin->post_filter->sortby]) :
            dcCore::app()->admin->post_filter->sortby) . ' ' . dcCore::app()->admin->post_filter->order;

        $params['no_content'] = true;

        // List
        // ----
        dcCore::app()->admin->post_list = null;

        try {
            $posts   = dcCore::app()->blog->getPosts($params);
            $counter = dcCore::app()->blog->getPosts($params, true);

            dcCore::app()->admin->post_list = new ListingPosts($posts, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        Page::open(
            __('Posts'),
            Page::jsLoad('js/_posts_list.js') . dcCore::app()->admin->post_filter->js(dcCore::app()->admin->url->get('admin.posts')),
            Page::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Posts')                                 => '',
                ]
            )
        );
        if (!empty($_GET['upd'])) {
            Notices::success(__('Selected entries have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            Notices::success(__('Selected entries have been successfully deleted.'));
        }
        if (!dcCore::app()->error->flag()) {
            echo
            '<p class="top-add"><a class="button add" href="' . dcCore::app()->admin->url->get('admin.post') . '">' . __('New post') . '</a></p>';

            # filters
            dcCore::app()->admin->post_filter->display('admin.posts');

            # Show posts
            dcCore::app()->admin->post_list->display(
                dcCore::app()->admin->post_filter->page,
                dcCore::app()->admin->post_filter->nb,
                '<form action="' . dcCore::app()->admin->url->get('admin.posts') . '" method="post" id="form-entries">' .
                // List
                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .
                // Actions
                '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
                form::combo('action', dcCore::app()->admin->posts_actions_page->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('ok') . '" disabled /></p>' .
                dcCore::app()->admin->url->getHiddenFormFields('admin.posts', dcCore::app()->admin->post_filter->values()) .
                Core::nonce()->getFormNonce() .
                '</div>' .
                '</form>',
                dcCore::app()->admin->post_filter->show()
            );
        }

        Page::helpBlock('core_posts');
        Page::close();
    }
}
