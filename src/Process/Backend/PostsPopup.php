<?php
/**
 * @since 2.27 Before as admin/popup_posts.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcCore;
use dcThemes;
use Dotclear\Core\Backend\Listing\ListingPostsMini;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class PostsPopup extends Process
{
    public static function init(): bool
    {
        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Core::backend()->q         = !empty($_GET['q']) ? $_GET['q'] : null;
        Core::backend()->plugin_id = !empty($_GET['plugin_id']) ? Html::sanitizeURL($_GET['plugin_id']) : '';

        Core::backend()->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        Core::backend()->nb_per_page = 10;

        Core::backend()->type = !empty($_GET['type']) ? $_GET['type'] : null;

        $post_types = Core::postTypes()->dump();
        $type_combo = [];
        foreach (array_keys($post_types) as $k) {
            $type_combo[__($k)] = (string) $k;
        }
        if (!in_array(Core::backend()->type, $type_combo)) {
            Core::backend()->type = null;
        }
        Core::backend()->type_combo = $type_combo;

        $params = [];

        $params['limit']      = [(Core::backend()->page - 1) * Core::backend()->nb_per_page, Core::backend()->nb_per_page];
        $params['no_content'] = true;
        $params['order']      = 'post_dt DESC';

        if (Core::backend()->q) {
            $params['search'] = Core::backend()->q;
        }

        if (Core::backend()->type) {
            $params['post_type'] = Core::backend()->type;
        }

        Core::backend()->params = $params;

        if (dcCore::app()->themes === null) {
            // Loading themes, may be useful for some configurable theme --
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(Core::blog()->themes_path, 'admin', Core::lang());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        Page::openPopup(
            __('Add a link to an entry'),
            Page::jsLoad('js/_posts_list.js') .
            Page::jsLoad('js/_popup_posts.js') .
            Core::behavior()->callBehavior('adminPopupPosts', Core::backend()->plugin_id)
        );

        echo
        '<h2 class="page-title">' . __('Add a link to an entry') . '</h2>';

        echo
        '<form action="' . Core::backend()->url->get('admin.posts.popup') . '" method="get">' .
        '<p><label for="type" class="classic">' . __('Entry type:') . '</label> ' . form::combo('type', Core::backend()->type_combo, Core::backend()->type) . '' .
        '<noscript><div><input type="submit" value="' . __('Ok') . '" /></div></noscript>' .
        form::hidden('plugin_id', Html::escapeHTML(Core::backend()->plugin_id)) .
        form::hidden('popup', 1) .
        form::hidden('process', 'PostsPopup') .
        '</p>' .
        '</form>';

        echo
        '<form action="' . Core::backend()->url->get('admin.posts.popup') . '" method="get">' .
        '<p><label for="q" class="classic">' . __('Search entry:') . '</label> ' . form::field('q', 30, 255, Html::escapeHTML(Core::backend()->q)) .
        ' <input type="submit" value="' . __('Search') . '" />' .
        form::hidden('plugin_id', Html::escapeHTML(Core::backend()->plugin_id)) .
        form::hidden('type', Html::escapeHTML(Core::backend()->type)) .
        form::hidden('popup', 1) .
        form::hidden('process', 'PostsPopup') .
        '</p></form>';

        $post_list = null;

        try {
            $posts     = Core::blog()->getPosts(Core::backend()->params);
            $counter   = Core::blog()->getPosts(Core::backend()->params, true);
            $post_list = new ListingPostsMini($posts, $counter->f(0));
        } catch (Exception $e) {
            Core::error()->add($e->getMessage());
        }

        echo '<div id="form-entries">'; // I know it's not a form but we just need the ID
        if ($post_list) {
            $post_list->display(Core::backend()->page, Core::backend()->nb_per_page);
        }
        echo '</div>';

        echo '<p><button type="button" id="link-insert-cancel">' . __('cancel') . '</button></p>';

        Page::closePopup();
    }
}
