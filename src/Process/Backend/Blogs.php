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

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsBlogs;
use Dotclear\Core\Backend\Filter\FilterBlogs;
use Dotclear\Core\Backend\Listing\ListingBlogs;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Schema\Extension\User;
use Exception;

/**
 * @since 2.27 Before as admin/blogs.php
 */
class Blogs extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        /* Actions
        -------------------------------------------------------- */
        App::backend()->blogs_actions_page = null;
        if (App::auth()->isSuperAdmin()) {
            App::backend()->blogs_actions_page = new ActionsBlogs(App::backend()->url()->get('admin.blogs'));
            if (App::backend()->blogs_actions_page->process()) {
                return false;
            }
        }

        /* Filters
        -------------------------------------------------------- */
        App::backend()->blog_filter = new FilterBlogs();

        // get list params
        $params = App::backend()->blog_filter->params();

        /* List
        -------------------------------------------------------- */
        App::backend()->blog_list = null;

        try {
            # --BEHAVIOR-- adminGetBlogs
            $params = new ArrayObject($params);
            # --BEHAVIOR-- adminGetBlogs -- ArrayObject
            App::behavior()->callBehavior('adminGetBlogs', $params);

            $counter  = App::blogs()->getBlogs($params, true);
            $rs       = App::blogs()->getBlogs($params);
            $rsStatic = $rs->toStatic();
            if ((App::backend()->blog_filter->sortby != 'blog_upddt') && (App::backend()->blog_filter->sortby != 'blog_status')) {
                // Sort blog list using lexical order if necessary
                $rsStatic->extend(User::class);
                $rsStatic = $rsStatic->toStatic();
                $rsStatic->lexicalSort((App::backend()->blog_filter->sortby == 'UPPER(blog_name)' ? 'blog_name' : 'blog_id'), App::backend()->blog_filter->order);
            }
            App::backend()->blog_list = new ListingBlogs($rs, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        // Nullsafe before header sent
        if (!App::task()->checkContext('BACKEND')) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        Page::open(
            __('List of blogs'),
            Page::jsLoad('js/_blogs.js') . App::backend()->blog_filter->js(App::backend()->url()->get('admin.blogs')),
            Page::breadcrumb(
                [
                    __('System')        => '',
                    __('List of blogs') => '',
                ]
            )
        );

        if (!App::error()->flag()) {
            if (App::auth()->isSuperAdmin()) {
                // Create blog button
                echo (new Para())
                    ->class('top-add')
                    ->items([
                        (new Link())
                            ->class(['button', 'add'])
                            ->href(App::backend()->url()->get('admin.blog'))
                            ->text(__('Create a new blog')),
                    ])
                    ->render();
            }

            App::backend()->blog_filter->display('admin.blogs');

            // Show blogs
            $form = null;
            if (App::auth()->isSuperAdmin()) {
                $form = (new Form('form-blogs'))
                        ->action(App::backend()->url()->get('admin.blogs'))
                        ->method('post')
                        ->fields([
                            // sprintf pattern for blog list
                            (new Text())->text('%s'),
                            (new Div())
                                ->class(['two-cols', 'clearfix'])
                                ->items([
                                    (new Para())->class(['col', 'checkboxes-helpers']),
                                    (new Para())->class(['col', 'right', 'form-buttons'])->items([
                                        (new Select('action'))
                                            ->class('online')
                                            ->title(__('Actions'))
                                            ->label(
                                                (new Label(
                                                    __('Selected blogs action:'),
                                                    Label::OUTSIDE_LABEL_BEFORE
                                                ))
                                                ->class('classic')
                                            )
                                            ->items(App::backend()->blogs_actions_page->getCombo()),
                                        App::nonce()->formNonce(),
                                        (new Submit('do-action'))
                                            ->value(__('ok')),
                                    ]),
                                ]),
                            (new Para())->items([
                                (new Password('pwd'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->autocomplete('current-password')
                                    ->label(
                                        (new Label(
                                            __('Please give your password to confirm blog(s) deletion:'),
                                            Label::OUTSIDE_LABEL_BEFORE
                                        ))
                                        ->class('classic')
                                    ),
                            ]),
                            ...App::backend()->url()->hiddenFormFields('admin.blogs', App::backend()->blog_filter->values(true)),
                        ]);
            }

            App::backend()->blog_list->display(
                App::backend()->blog_filter->page,
                App::backend()->blog_filter->nb,
                App::auth()->isSuperAdmin() ? $form->render() : '%s',
                App::backend()->blog_filter->show()
            );
        }

        Page::helpBlock('core_blogs');
        Page::close();
    }
}
