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
use Dotclear\Core\Backend\Action\ActionsBlogs;
use Dotclear\Core\Backend\Filter\FilterBlogs;
use Dotclear\Core\Backend\Listing\ListingBlogs;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
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
        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        /* Actions
        -------------------------------------------------------- */
        Core::backend()->blogs_actions_page = null;
        if (Core::auth()->isSuperAdmin()) {
            Core::backend()->blogs_actions_page = new ActionsBlogs(Core::backend()->url->get('admin.blogs'));
            if (Core::backend()->blogs_actions_page->process()) {
                return false;
            }
        }

        /* Filters
        -------------------------------------------------------- */
        Core::backend()->blog_filter = new FilterBlogs();

        // get list params
        $params = Core::backend()->blog_filter->params();

        /* List
        -------------------------------------------------------- */
        Core::backend()->blog_list = null;

        try {
            # --BEHAVIOR-- adminGetBlogs
            $params = new ArrayObject($params);
            # --BEHAVIOR-- adminGetBlogs -- ArrayObject
            Core::behavior()->callBehavior('adminGetBlogs', $params);

            $counter  = Core::blogs()->getBlogs($params, true);
            $rs       = Core::blogs()->getBlogs($params);
            $rsStatic = $rs->toStatic();
            if ((Core::backend()->blog_filter->sortby != 'blog_upddt') && (Core::backend()->blog_filter->sortby != 'blog_status')) {
                // Sort blog list using lexical order if necessary
                $rsStatic->extend('rsExtUser');
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort((Core::backend()->blog_filter->sortby == 'UPPER(blog_name)' ? 'blog_name' : 'blog_id'), Core::backend()->blog_filter->order);
            }
            Core::backend()->blog_list = new ListingBlogs($rs, $counter->f(0));
        } catch (Exception $e) {
            Core::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        // Nullsafe before header sent
        if (is_null(Core::auth())) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        Page::open(
            __('List of blogs'),
            Page::jsLoad('js/_blogs.js') . Core::backend()->blog_filter->js(Core::backend()->url->get('admin.blogs')),
            Page::breadcrumb(
                [
                    __('System')        => '',
                    __('List of blogs') => '',
                ]
            )
        );

        if (!Core::error()->flag()) {
            if (Core::auth()->isSuperAdmin()) {
                // Create blog button
                echo (new Para())
                    ->class('top-add')
                    ->items([
                        (new Link())
                            ->class(['button', 'add'])
                            ->href(Core::backend()->url->get('admin.blog'))
                            ->text(__('Create a new blog')),
                    ])
                    ->render();
            }

            Core::backend()->blog_filter->display('admin.blogs');

            // Show blogs
            $form = null;
            if (Core::auth()->isSuperAdmin()) {
                $form = (new Form('form-blogs'))
                        ->action(Core::backend()->url->get('admin.blogs'))
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
                                            ->items(Core::backend()->blogs_actions_page->getCombo()),
                                        Core::nonce()->formNonce(),
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
                            ...Core::backend()->url->hiddenFormFields('admin.blogs', Core::backend()->blog_filter->values(true)),
                        ]);
            }

            Core::backend()->blog_list->display(
                Core::backend()->blog_filter->page,
                Core::backend()->blog_filter->nb,
                Core::auth()->isSuperAdmin() ? $form->render() : '%s',
                Core::backend()->blog_filter->show()
            );
        }

        Page::helpBlock('core_blogs');
        Page::close();
    }
}
