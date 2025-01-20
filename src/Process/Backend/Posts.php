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
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Filter\FilterPosts;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @since 2.27 Before as admin/posts.php
 */
class Posts extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        // Actions
        // -------
        App::backend()->posts_actions_page = new ActionsPosts(App::backend()->url()->get('admin.posts'));
        if (App::backend()->posts_actions_page->process()) {
            return self::status(false);
        }

        // Filters
        // -------
        App::backend()->post_filter = new FilterPosts();

        // get list params
        $params = App::backend()->post_filter->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'post_title' => 'post_title',
            'cat_title'  => 'cat_title',
            'user_id'    => 'P.user_id', ];

        # --BEHAVIOR-- adminPostsSortbyLexCombo -- array<int,array<string,string>>
        App::behavior()->callBehavior('adminPostsSortbyLexCombo', [&$sortby_lex]);

        $params['order'] = (array_key_exists(App::backend()->post_filter->sortby, $sortby_lex) ?
            App::con()->lexFields($sortby_lex[App::backend()->post_filter->sortby]) :
            App::backend()->post_filter->sortby) . ' ' . App::backend()->post_filter->order;

        $params['no_content'] = true;

        // List
        // ----
        App::backend()->post_list = null;

        try {
            $posts   = App::blog()->getPosts($params);
            $counter = App::blog()->getPosts($params, true);

            App::backend()->post_list = new ListingPosts($posts, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        Page::open(
            __('Posts'),
            Page::jsLoad('js/_posts_list.js') . App::backend()->post_filter->js(App::backend()->url()->get('admin.posts')),
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Posts')                           => '',
                ]
            )
        );
        if (!empty($_GET['upd'])) {
            Notices::success(__('Selected entries have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            Notices::success(__('Selected entries have been successfully deleted.'));
        }
        if (!App::error()->flag()) {
            echo (new Para())
                ->class('top-add')
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.post'))
                        ->class(['button', 'add'])
                        ->text(__('New post')),
                ])
            ->render();

            # filters
            App::backend()->post_filter->display('admin.posts');

            # Show posts
            $combo = App::backend()->posts_actions_page->getCombo();
            if (is_array($combo)) {
                $block = (new Form('form-entries'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.posts'))
                    ->fields([
                        (new Text(null, '%s')), // Here will go the posts list
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Select('action'))
                                            ->items($combo)
                                            ->label(new Label(__('Selected entries action:'), Label::IL_TF)),
                                        (new Submit('do-action', __('ok')))
                                            ->disabled(true),
                                        App::nonce()->formNonce(),
                                        ... App::backend()->url()->hiddenFormFields('admin.posts', App::backend()->post_filter->values()),
                                    ]),
                            ]),
                    ])
                ->render();
            } else {
                $block = (new Text(null, '%s'))
                ->render();
            }

            App::backend()->post_list->display(
                App::backend()->post_filter->page,
                App::backend()->post_filter->nb,
                $block,
                App::backend()->post_filter->show()
            );
        }

        Page::helpBlock('core_posts');
        Page::close();
    }
}
