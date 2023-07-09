<?php
/**
 * @since 2.27 Before as admin/blogs.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use dcCore;
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
use Exception;

class Blogs extends Process
{
    public static function init(): bool
    {
        Page::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]));

        /* Actions
        -------------------------------------------------------- */
        dcCore::app()->admin->blogs_actions_page = null;
        if (dcCore::app()->auth->isSuperAdmin()) {
            dcCore::app()->admin->blogs_actions_page = new ActionsBlogs(dcCore::app()->admin->url->get('admin.blogs'));
            if (dcCore::app()->admin->blogs_actions_page->process()) {
                return false;
            }
        }

        /* Filters
        -------------------------------------------------------- */
        dcCore::app()->admin->blog_filter = new FilterBlogs();

        // get list params
        $params = dcCore::app()->admin->blog_filter->params();

        /* List
        -------------------------------------------------------- */
        dcCore::app()->admin->blog_list = null;

        try {
            # --BEHAVIOR-- adminGetBlogs
            $params = new ArrayObject($params);
            # --BEHAVIOR-- adminGetBlogs -- ArrayObject
            dcCore::app()->callBehavior('adminGetBlogs', $params);

            $counter  = dcCore::app()->getBlogs($params, true);
            $rs       = dcCore::app()->getBlogs($params);
            $rsStatic = $rs->toStatic();
            if ((dcCore::app()->admin->blog_filter->sortby != 'blog_upddt') && (dcCore::app()->admin->blog_filter->sortby != 'blog_status')) {
                // Sort blog list using lexical order if necessary
                $rsStatic->extend('rsExtUser');
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort((dcCore::app()->admin->blog_filter->sortby == 'UPPER(blog_name)' ? 'blog_name' : 'blog_id'), dcCore::app()->admin->blog_filter->order);
            }
            dcCore::app()->admin->blog_list = new ListingBlogs($rs, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        // Nullsafe before header sent
        if (!isset(dcCore::app()->auth)) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        Page::open(
            __('List of blogs'),
            Page::jsLoad('js/_blogs.js') . dcCore::app()->admin->blog_filter->js(dcCore::app()->admin->url->get('admin.blogs')),
            Page::breadcrumb(
                [
                    __('System')        => '',
                    __('List of blogs') => '',
                ]
            )
        );

        if (!dcCore::app()->error->flag()) {
            if (dcCore::app()->auth->isSuperAdmin()) {
                // Create blog button
                echo (new Para())
                    ->class('top-add')
                    ->items([
                        (new Link())
                            ->class(['button', 'add'])
                            ->href(dcCore::app()->admin->url->get('admin.blog'))
                            ->text(__('Create a new blog')),
                    ])
                    ->render();
            }

            dcCore::app()->admin->blog_filter->display('admin.blogs');

            // Show blogs
            $form = null;
            if (dcCore::app()->auth->isSuperAdmin()) {
                $form = (new Form('form-blogs'))
                        ->action(dcCore::app()->admin->url->get('admin.blogs'))
                        ->method('post')
                        ->fields([
                            // sprintf pattern for blog list
                            (new Text())->text('%s'),
                            (new Div())
                                ->class(['two-cols', 'clearfix'])
                                ->items([
                                    (new Para())->class(['col checkboxes-helpers']),
                                    (new Para())->class(['col right'])->items([
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
                                            ->items(dcCore::app()->admin->blogs_actions_page->getCombo()),
                                        dcCore::app()->formNonce(false),
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
                            ...dcCore::app()->admin->url->hiddenFormFields('admin.blogs', dcCore::app()->admin->blog_filter->values(true)),
                        ]);
            }

            dcCore::app()->admin->blog_list->display(
                dcCore::app()->admin->blog_filter->page,
                dcCore::app()->admin->blog_filter->nb,
                dcCore::app()->auth->isSuperAdmin() ? $form->render() : '%s',
                dcCore::app()->admin->blog_filter->show()
            );
        }

        Page::helpBlock('core_blogs');
        Page::close();
    }
}
