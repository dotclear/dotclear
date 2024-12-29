<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Core\Backend\Listing\ListingPostsMini;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/popup_posts.php
 */
class PostsPopup extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        App::backend()->q           = $_GET['q'] ?? null;
        App::backend()->plugin_id   = empty($_GET['plugin_id']) ? '' : Html::sanitizeURL($_GET['plugin_id']);
        App::backend()->page        = empty($_GET['page']) ? 1 : max(1, (int) $_GET['page']);
        App::backend()->nb_per_page = 10;
        App::backend()->type        = $_GET['type'] ?? null;

        $post_types = App::postTypes()->dump();
        $type_combo = [];
        foreach (array_keys($post_types) as $k) {
            $type_combo[__($k)] = (string) $k;
        }
        if (!in_array(App::backend()->type, $type_combo)) {
            App::backend()->type = null;
        }
        App::backend()->type_combo = $type_combo;

        $params = [];

        $params['limit']      = [(App::backend()->page - 1) * App::backend()->nb_per_page, App::backend()->nb_per_page];
        $params['no_content'] = true;
        $params['order']      = 'post_dt DESC';

        if (App::backend()->q) {
            $params['search'] = App::backend()->q;
        }

        if (App::backend()->type) {
            $params['post_type'] = App::backend()->type;
        }

        App::backend()->params = $params;

        if (App::themes()->isEmpty()) {
            // Loading themes, may be useful for some configurable theme --
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        Page::openPopup(
            __('Add a link to an entry'),
            Page::jsLoad('js/_posts_list.js') .
            Page::jsLoad('js/_popup_posts.js') .
            App::behavior()->callBehavior('adminPopupPosts', App::backend()->plugin_id)
        );

        echo
        '<h2 class="page-title">' . __('Add a link to an entry') . '</h2>';

        echo
        '<form action="' . App::backend()->url()->get('admin.posts.popup') . '" method="get">' .
        '<p><label for="type" class="classic">' . __('Entry type:') . '</label> ' . form::combo('type', App::backend()->type_combo, App::backend()->type) . '' .
        '<noscript><div><input type="submit" value="' . __('Ok') . '"></div></noscript>' .
        form::hidden('plugin_id', Html::escapeHTML(App::backend()->plugin_id)) .
        form::hidden('popup', 1) .
        form::hidden('process', 'PostsPopup') .
        '</p>' .
        '</form>';

        echo
        '<form action="' . App::backend()->url()->get('admin.posts.popup') . '" method="get">' .
        '<p><label for="q" class="classic">' . __('Search entry:') . '</label> ' . form::field('q', 30, 255, Html::escapeHTML(App::backend()->q)) .
        ' <input type="submit" value="' . __('Search') . '">' .
        form::hidden('plugin_id', Html::escapeHTML(App::backend()->plugin_id)) .
        form::hidden('type', Html::escapeHTML(App::backend()->type)) .
        form::hidden('popup', 1) .
        form::hidden('process', 'PostsPopup') .
        '</p></form>';

        $post_list = null;

        try {
            $posts     = App::blog()->getPosts(App::backend()->params);
            $counter   = App::blog()->getPosts(App::backend()->params, true);
            $post_list = new ListingPostsMini($posts, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        echo '<div id="form-entries">'; // I know it's not a form but we just need the ID
        if ($post_list instanceof ListingPostsMini) {
            $post_list->display(App::backend()->page, App::backend()->nb_per_page);
        }
        echo '</div>';

        echo '<p><button type="button" id="link-insert-cancel">' . __('cancel') . '</button></p>';

        Page::closePopup();
    }
}
