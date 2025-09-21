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
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/blogs.php
 */
class Blogs
{
    use TraitProcess;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        /* Actions
        -------------------------------------------------------- */
        App::backend()->blogs_actions_page = null;
        if (App::auth()->isSuperAdmin()) {
            App::backend()->blogs_actions_page = App::backend()->action()->blogs(App::backend()->url()->get('admin.blogs'));
            if (App::backend()->blogs_actions_page->process()) {
                return false;
            }
        }

        /* Filters
        -------------------------------------------------------- */
        App::backend()->blog_filter = App::backend()->filter()->blogs(); // Backward compatibility

        // get list params
        $params = App::backend()->filter()->blogs()->params();

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
            if ((App::backend()->filter()->blogs()->sortby != 'blog_upddt') && (App::backend()->filter()->blogs()->sortby != 'blog_status')) {
                // Sort blog list using lexical order if necessary
                $rsStatic->extend(User::class);
                $rsStatic = $rsStatic->toStatic();
                $rsStatic->lexicalSort((App::backend()->filter()->blogs()->sortby == 'UPPER(blog_name)' ? 'blog_name' : 'blog_id'), App::backend()->filter()->blogs()->order);
            }
            App::backend()->blog_list = App::backend()->listing()->blogs($rs, (int) $counter->f(0));
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

        App::backend()->page()->open(
            __('List of blogs'),
            App::backend()->page()->jsLoad('js/_blogs.js') . App::backend()->filter()->blogs()->js(App::backend()->url()->get('admin.blogs')),
            App::backend()->page()->breadcrumb(
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
                    ->class('new-stuff')
                    ->items([
                        (new Link())
                            ->class(['button', 'add'])
                            ->href(App::backend()->url()->get('admin.blog'))
                            ->text(__('Create a new blog')),
                    ])
                    ->render();
            }

            App::backend()->filter()->blogs()->display('admin.blogs');

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
                                    ->translate(false)
                                    ->label(
                                        (new Label(
                                            __('Please give your password to confirm blog(s) deletion:'),
                                            Label::OUTSIDE_LABEL_BEFORE
                                        ))
                                        ->class('classic')
                                    ),
                            ]),
                            ...App::backend()->url()->hiddenFormFields('admin.blogs', App::backend()->filter()->blogs()->values(true)),
                        ]);
            }

            App::backend()->blog_list->display(
                App::backend()->filter()->blogs()->page,
                App::backend()->filter()->blogs()->nb,
                App::auth()->isSuperAdmin() ? $form->render() : '%s',
                App::backend()->filter()->blogs()->show()
            );
        }

        App::backend()->page()->helpBlock('core_blogs');
        App::backend()->page()->close();
    }
}
