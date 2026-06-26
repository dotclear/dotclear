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
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/posts.php
 */
class Posts
{
    use TraitProcess;

    protected static ActionsPosts $posts_actions_page;

    protected static ListingPosts $post_list;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        // Actions
        // -------
        self::$posts_actions_page = App::backend()->action()->posts(App::backend()->url()->get('admin.posts'));
        if (self::$posts_actions_page->process()) {
            return self::status(false);
        }

        // Filters
        // -------
        App::backend()->post_filter = App::backend()->filter()->posts(); // Backward compatibility

        // get list params
        $params = App::backend()->filter()->posts()->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'post_title' => 'post_title',
            'cat_title'  => 'cat_title',
            'user_id'    => 'P.user_id',
        ];

        # --BEHAVIOR-- adminPostsSortbyLexCombo -- array<int,array<string,string>>
        App::behavior()->callBehavior('adminPostsSortbyLexCombo', [&$sortby_lex]);

        $sortby = is_string($sortby = App::backend()->filter()->posts()->sortby) ? $sortby : '';
        $order  = is_string($order = App::backend()->filter()->posts()->order) ? $order : '';

        $params['order'] = (array_key_exists($sortby, $sortby_lex) ? App::db()->con()->lexFields($sortby_lex[$sortby]) : $sortby) . ' ' . $order;

        $params['no_content'] = true;

        // Prepare list
        try {
            $posts   = App::blog()->getPosts($params);
            $counter = App::blog()->getPosts($params, true);

            self::$post_list = App::backend()->listing()->posts($posts, $counter->cardinal());
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        App::backend()->page()->open(
            __('Posts'),
            App::backend()->page()->jsLoad('js/_posts_list.js') . App::backend()->filter()->posts()->js(App::backend()->url()->get('admin.posts')),
            App::backend()->page()->breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Posts')                           => '',
                ]
            )
        );
        if (!empty($_GET['upd'])) {
            App::backend()->notices()->success(__('Selected entries have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            App::backend()->notices()->success(__('Selected entries have been successfully deleted.'));
        }
        if (!App::error()->flag()) {
            echo (new Para())
                ->class('new-stuff')
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.post'))
                        ->class(['button', 'add'])
                        ->text(__('New post')),
                ])
            ->render();

            # filters
            App::backend()->filter()->posts()->display('admin.posts');

            # Show posts
            $page = is_numeric($page = App::backend()->filter()->posts()->page) ? (int) $page : 0;
            $nb   = is_numeric($nb = App::backend()->filter()->posts()->nb) ? (int) $nb : 30;

            $combo = self::$posts_actions_page->getCombo();
            if ($combo !== []) {
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
                                        ... App::backend()->url()->hiddenFormFields('admin.posts', App::backend()->filter()->posts()->values()),
                                    ]),
                            ]),
                    ])
                ->render();
            } else {
                $block = (new Text(null, '%s'))
                ->render();
            }

            self::$post_list->display(
                $page,
                $nb,
                $block,
                App::backend()->filter()->posts()->show()
            );
        }

        App::backend()->page()->helpBlock('core_posts');
        App::backend()->page()->close();
    }
}
