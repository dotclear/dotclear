<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminPosts
{
    /**
     * Initializes the page.
     *
     * @return bool     If we should return after init
     */
    public static function init(): bool
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        // Actions
        // -------
        dcCore::app()->admin->posts_actions_page = new dcPostsActions(dcCore::app()->adminurl->get('admin.posts'));
        if (dcCore::app()->admin->posts_actions_page->process()) {
            return true;
        }

        // Filters
        // -------
        dcCore::app()->admin->post_filter = new adminPostFilter();

        // get list params
        $params = dcCore::app()->admin->post_filter->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'post_title' => 'post_title',
            'cat_title'  => 'cat_title',
            'user_id'    => 'P.user_id', ];

        # --BEHAVIOR-- adminPostsSortbyLexCombo
        dcCore::app()->callBehavior('adminPostsSortbyLexCombo', [& $sortby_lex]);

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

            dcCore::app()->admin->post_list = new adminPostList($posts, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return false;
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        dcPage::open(
            __('Posts'),
            dcPage::jsLoad('js/_posts_list.js') . dcCore::app()->admin->post_filter->js(),
            dcPage::breadcrumb(
                [
                    html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Posts')                                 => '',
                ]
            )
        );
        if (!empty($_GET['upd'])) {
            dcPage::success(__('Selected entries have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            dcPage::success(__('Selected entries have been successfully deleted.'));
        }
        if (!dcCore::app()->error->flag()) {
            echo
            '<p class="top-add"><a class="button add" href="' . dcCore::app()->adminurl->get('admin.post') . '">' . __('New post') . '</a></p>';

            # filters
            dcCore::app()->admin->post_filter->display('admin.posts');

            # Show posts
            dcCore::app()->admin->post_list->display(
                dcCore::app()->admin->post_filter->page,
                dcCore::app()->admin->post_filter->nb,
                '<form action="' . dcCore::app()->adminurl->get('admin.posts') . '" method="post" id="form-entries">' .
                // List
                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .
                // Actions
                '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
                form::combo('action', dcCore::app()->admin->posts_actions_page->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('ok') . '" disabled /></p>' .
                dcCore::app()->adminurl->getHiddenFormFields('admin.posts', dcCore::app()->admin->post_filter->values()) .
                dcCore::app()->formNonce() .
                '</div>' .
                '</form>',
                dcCore::app()->admin->post_filter->show()
            );
        }

        dcPage::helpBlock('core_posts');
        dcPage::close();
    }
}

if (adminPosts::init()) {
    return;
}
adminPosts::render();
