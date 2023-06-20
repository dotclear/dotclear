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

namespace Dotclear\Backend;

use adminBlogFilter;
use adminBlogList;
use ArrayObject;
use dcBlogsActions;
use dcCore;
use dcNsProcess;
use dcPage;
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

class Blogs extends dcNsProcess
{
    /**
     * Initializes the page.
     *
     * @return     bool  False if we must return immediatly
     */
    public static function init(): bool
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]));

        /* Actions
        -------------------------------------------------------- */
        dcCore::app()->admin->blogs_actions_page = null;
        if (dcCore::app()->auth->isSuperAdmin()) {
            dcCore::app()->admin->blogs_actions_page = new dcBlogsActions(dcCore::app()->adminurl->get('admin.blogs'));
            if (dcCore::app()->admin->blogs_actions_page->process()) {
                return false;
            }
        }

        /* Filters
        -------------------------------------------------------- */
        dcCore::app()->admin->blog_filter = new adminBlogFilter();

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
            dcCore::app()->admin->blog_list = new adminBlogList($rs, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        dcPage::open(
            __('List of blogs'),
            dcPage::jsLoad('js/_blogs.js') . dcCore::app()->admin->blog_filter->js(dcCore::app()->adminurl->get('admin.blogs')),
            dcPage::breadcrumb(
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
                            ->href(dcCore::app()->adminurl->get('admin.blog'))
                            ->text(__('Create a new blog')),
                    ])
                    ->render();
            }

            dcCore::app()->admin->blog_filter->display('admin.blogs');

            // Show blogs
            $form = null;
            if (dcCore::app()->auth->isSuperAdmin()) {
                $form = (new Form('form-blogs'))
                        ->action(dcCore::app()->adminurl->get('admin.blogs'))
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
                            ...dcCore::app()->adminurl->hiddenFormFields('admin.blogs', dcCore::app()->admin->blog_filter->values(true)),
                        ]);
            }

            dcCore::app()->admin->blog_list->display(
                dcCore::app()->admin->blog_filter->page,
                dcCore::app()->admin->blog_filter->nb,
                dcCore::app()->auth->isSuperAdmin() ? $form->render() : '%s',
                dcCore::app()->admin->blog_filter->show()
            );
        }

        dcPage::helpBlock('core_blogs');
        dcPage::close();
    }
}
