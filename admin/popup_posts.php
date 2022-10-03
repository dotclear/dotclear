<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminPopupPosts
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        dcCore::app()->admin->q         = !empty($_GET['q']) ? $_GET['q'] : null;
        dcCore::app()->admin->plugin_id = !empty($_GET['plugin_id']) ? html::sanitizeURL($_GET['plugin_id']) : '';

        dcCore::app()->admin->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        dcCore::app()->admin->nb_per_page = 10;

        dcCore::app()->admin->type = !empty($_GET['type']) ? $_GET['type'] : null;

        $post_types = dcCore::app()->getPostTypes();
        $type_combo = [];
        foreach (array_keys($post_types) as $k) {
            $type_combo[__($k)] = (string) $k;
        }
        if (!in_array(dcCore::app()->admin->type, $type_combo)) {
            dcCore::app()->admin->type = null;
        }
        dcCore::app()->admin->type_combo = $type_combo;

        $params = [];

        $params['limit']      = [(dcCore::app()->admin->page - 1) * dcCore::app()->admin->nb_per_page, dcCore::app()->admin->nb_per_page];
        $params['no_content'] = true;
        $params['order']      = 'post_dt DESC';

        if (dcCore::app()->admin->q) {
            $params['search'] = dcCore::app()->admin->q;
        }

        if (dcCore::app()->admin->type) {
            $params['post_type'] = dcCore::app()->admin->type;
        }

        dcCore::app()->admin->params = $params;

        if (dcCore::app()->themes === null) {
            // Loading themes, may be useful for some configurable theme --
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        dcPage::openPopup(
            __('Add a link to an entry'),
            dcPage::jsLoad('js/_posts_list.js') .
            dcPage::jsLoad('js/_popup_posts.js') .
            dcCore::app()->callBehavior('adminPopupPosts', dcCore::app()->admin->plugin_id)
        );

        echo
        '<h2 class="page-title">' . __('Add a link to an entry') . '</h2>';

        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.popup_posts') . '" method="get">' .
        '<p><label for="type" class="classic">' . __('Entry type:') . '</label> ' . form::combo('type', dcCore::app()->admin->type_combo, dcCore::app()->admin->type) . '' .
        '<noscript><div><input type="submit" value="' . __('Ok') . '" /></div></noscript>' .
        form::hidden('plugin_id', html::escapeHTML(dcCore::app()->admin->plugin_id)) . '</p>' .
        '</form>';

        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.popup_posts') . '" method="get">' .
        '<p><label for="q" class="classic">' . __('Search entry:') . '</label> ' . form::field('q', 30, 255, html::escapeHTML(dcCore::app()->admin->q)) .
        ' <input type="submit" value="' . __('Search') . '" />' .
        form::hidden('plugin_id', html::escapeHTML(dcCore::app()->admin->plugin_id)) .
        form::hidden('type', html::escapeHTML(dcCore::app()->admin->type)) .
        '</p></form>';

        $post_list = null;

        try {
            $posts     = dcCore::app()->blog->getPosts(dcCore::app()->admin->params);
            $counter   = dcCore::app()->blog->getPosts(dcCore::app()->admin->params, true);
            $post_list = new adminPostMiniList($posts, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        echo '<div id="form-entries">'; // I know it's not a form but we just need the ID
        if ($post_list) {
            $post_list->display(dcCore::app()->admin->page, dcCore::app()->admin->nb_per_page);
        }
        echo '</div>';

        echo '<p><button type="button" id="link-insert-cancel">' . __('cancel') . '</button></p>';

        dcPage::closePopup();
    }
}

adminPopupPosts::init();
adminPopupPosts::render();
