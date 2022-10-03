<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminBlogs
{
    /**
     * Initializes the page.
     *
     * @return     bool  True if we must return immediatly
     */
    public static function init(): bool
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        /* Actions
        -------------------------------------------------------- */
        dcCore::app()->admin->blogs_actions_page = null;
        if (dcCore::app()->auth->isSuperAdmin()) {
            dcCore::app()->admin->blogs_actions_page = new dcBlogsActions(dcCore::app()->adminurl->get('admin.blogs'));
            if (dcCore::app()->admin->blogs_actions_page->process()) {
                return true;
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

        return false;
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        dcPage::open(
            __('List of blogs'),
            dcPage::jsLoad('js/_blogs.js') . dcCore::app()->admin->blog_filter->js(),
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
                echo (new formPara())
                    ->class('top-add')
                    ->items([
                        (new formLink())
                            ->class(['button', 'add'])
                            ->href(dcCore::app()->adminurl->get('admin.blog'))
                            ->text(__('Create a new blog')),
                    ])
                    ->render();
            }

            dcCore::app()->admin->blog_filter->display('admin.blogs');

            // Show blogs
            $form = (new formForm('form-blogs'))
                    ->action(dcCore::app()->adminurl->get('admin.blogs'))
                    ->method('post')
                    ->fields([
                        // sprintf pattern for blog list
                        (new formText())->text('%s'),
                        (new formDiv())
                            ->class(['two-cols', 'clearfix'])
                            ->items([
                                (new formPara())->class(['col checkboxes-helpers']),
                                (new formPara())->class(['col right'])->items([
                                    (new formSelect('action'))
                                        ->class('online')
                                        ->title(__('Actions'))
                                        ->label(
                                            (new formLabel(
                                                __('Selected blogs action:'),
                                                formLabel::OUTSIDE_LABEL_BEFORE
                                            ))
                                            ->class('classic')
                                        )
                                        ->items(dcCore::app()->admin->blogs_actions_page->getCombo()),
                                    dcCore::app()->formNonce(false),
                                    (new formSubmit(['do-action']))
                                        ->value(__('ok')),
                                ]),
                            ]),
                        (new formPara())->items([
                            (new formPassword('pwd'))
                                ->size(20)
                                ->maxlength(255)
                                ->autocomplete('current-password')
                                ->label(
                                    (new formLabel(
                                        __('Please give your password to confirm blog(s) deletion:'),
                                        formLabel::OUTSIDE_LABEL_BEFORE
                                    ))
                                    ->class('classic')
                                ),
                        ]),
                        ...dcCore::app()->adminurl->hiddenFormFields('admin.blogs', dcCore::app()->admin->blog_filter->values(true)),
                    ]);

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

if (adminBlogs::init()) {
    return;
}
adminBlogs::render();
