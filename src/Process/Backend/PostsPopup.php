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
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

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
        $post_list = null;

        try {
            $posts     = App::blog()->getPosts(App::backend()->params);
            $counter   = App::blog()->getPosts(App::backend()->params, true);
            $post_list = new ListingPostsMini($posts, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        Page::openPopup(
            __('Add a link to an entry'),
            Page::jsLoad('js/_posts_list.js') .
            Page::jsLoad('js/_popup_posts.js') .
            App::behavior()->callBehavior('adminPopupPosts', App::backend()->plugin_id)
        );

        echo
        (new Set())
            ->items([
                (new Text('h2', __('Add a link to an entry')))
                    ->class('page-title'),
                (new Form('entry-type-form'))
                    ->method('get')
                    ->action(App::backend()->url()->get('admin.posts.popup'))
                    ->fields([
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Select('type'))
                                    ->items(App::backend()->type_combo)
                                    ->default(App::backend()->type)
                                    ->label(new Label(__('Entry type:'), Label::IL_TF)),
                                (new Submit('type-submit', __('Ok'))),
                                (new Hidden('plugin_id', Html::escapeHTML(App::backend()->plugin_id))),
                                (new Hidden('popup', '1')),
                                (new Hidden('process', 'PostsPopup')),
                            ]),
                    ]),
                (new Form('entry-search-form'))
                    ->method('get')
                    ->action(App::backend()->url()->get('admin.posts.popup'))
                    ->fields([
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Input('q'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->q))
                                    ->label(new Label(__('Search entry:'), Label::IL_TF)),
                                (new Submit('search-submit', __('Search'))),
                                (new Hidden('plugin_id', Html::escapeHTML(App::backend()->plugin_id))),
                                (new Hidden('popup', '1')),
                                (new Hidden('process', 'PostsPopup')),
                                (new Hidden('type', App::backend()->type)),
                            ]),
                    ]),
                (new Div('form-entries'))   // I know it's not a form but we just need the ID
                    ->items([
                        $post_list instanceof ListingPostsMini ?
                        (new Capture($post_list->display(...), [App::backend()->page, App::backend()->nb_per_page])) :
                        (new None()),
                    ]),
                (new Para())
                    ->items([
                        (new Button('link-insert-cancel', __('cancel'))),
                    ]),
            ])
        ->render();

        Page::closePopup();
    }
}
