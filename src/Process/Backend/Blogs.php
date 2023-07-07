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
    private static ActionsBlogs $blogs_actions_page;
    private static FilterBlogs $blog_filter;
    private static ListingBlogs $blog_list;

    public static function init(): bool
    {
        // Nullsafe php 7.4
        if (is_null(dcCore::app()->auth)) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        // Check user permissions
        Page::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]));

        // Check and process blogs action
        if (dcCore::app()->auth->isSuperAdmin()) {
            self::$blogs_actions_page = new ActionsBlogs(dcCore::app()->admin->url->get('admin.blogs'));
            if (self::$blogs_actions_page->process()) {
                return false;
            }
        }

        // Instanciate blogs list filters
        self::$blog_filter = new FilterBlogs();

        // Get records and set list
        try {
            $params = new ArrayObject(self::$blog_filter->params());

            # --BEHAVIOR-- adminGetBlogs -- ArrayObject
            dcCore::app()->callBehavior('adminGetBlogs', $params);

            $counter  = dcCore::app()->getBlogs($params, true);
            $rs       = dcCore::app()->getBlogs($params);
            $rsStatic = $rs->toStatic();
            if ((self::$blog_filter->sortby != 'blog_upddt') && (self::$blog_filter->sortby != 'blog_status')) {
                // Sort blog list using lexical order if necessary
                $rsStatic->extend('rsExtUser');
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort((self::$blog_filter->sortby == 'UPPER(blog_name)' ? 'blog_name' : 'blog_id'), self::$blog_filter->order);
            }
            self::$blog_list = new ListingBlogs($rs, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        // Nullsafe php 7.4
        if (is_null(dcCore::app()->auth)) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        Page::open(
            __('List of blogs'),
            Page::jsLoad('js/_blogs.js') . self::$blog_filter->js(dcCore::app()->admin->url->get('admin.blogs')),
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

            self::$blog_filter->display('admin.blogs');

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
                                            ->items(self::$blogs_actions_page->getCombo()),
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
                            ...dcCore::app()->admin->url->hiddenFormFields('admin.blogs', self::$blog_filter->values(true)),
                        ]);
            }

            self::$blog_list->display(
                self::$blog_filter->page,
                self::$blog_filter->nb,
                dcCore::app()->auth->isSuperAdmin() ? $form->render() : '%s',
                self::$blog_filter->show()
            );
        }

        Page::helpBlock('core_blogs');
        Page::close();
    }
}
