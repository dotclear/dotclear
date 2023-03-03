<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;

require __DIR__ . '/../inc/admin/prepend.php';

class adminBlogDel
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcPage::checkSuper();

        dcCore::app()->admin->blog_id   = '';
        dcCore::app()->admin->blog_name = '';

        if (!empty($_POST['blog_id'])) {
            $rs = null;

            try {
                $rs = dcCore::app()->getBlog($_POST['blog_id']);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            if ($rs) {
                if ($rs->isEmpty()) {
                    dcCore::app()->error->add(__('No such blog ID'));
                } else {
                    dcCore::app()->admin->blog_id   = $rs->blog_id;
                    dcCore::app()->admin->blog_name = $rs->blog_name;
                }
            }
        }
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        if (!dcCore::app()->error->flag() && dcCore::app()->admin->blog_id && !empty($_POST['del'])) {
            // Delete the blog
            if (!dcCore::app()->auth->checkPassword($_POST['pwd'])) {
                dcCore::app()->error->add(__('Password verification failed'));
            } else {
                try {
                    dcCore::app()->delBlog(dcCore::app()->admin->blog_id);
                    dcPage::addSuccessNotice(sprintf(__('Blog "%s" successfully deleted'), html::escapeHTML(dcCore::app()->admin->blog_name)));

                    dcCore::app()->adminurl->redirect('admin.blogs');
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        dcPage::open(
            __('Delete a blog'),
            '',
            dcPage::breadcrumb(
                [
                    __('System')        => '',
                    __('Blogs')         => dcCore::app()->adminurl->get('admin.blogs'),
                    __('Delete a blog') => '',
                ]
            )
        );

        if (!dcCore::app()->error->flag()) {
            $msg = '<strong>' . __('Warning') . '</strong></p><p>' . sprintf(
                __('You are about to delete the blog %s. Every entry, comment and category will be deleted.'),
                '<strong>' . dcCore::app()->admin->blog_id . ' (' . dcCore::app()->admin->blog_name . ')</strong>'
            );
            dcAdminNotices::warning($msg, false, true);

            echo
            // Legend
            (new Para())
            ->items([
                (new Text())->text(__('Please give your password to confirm the blog deletion.')),
            ])->render() .
            // Form
            (new Form('form-del'))
            ->action(dcCore::app()->adminurl->get('admin.blog.del'))
            ->method('post')
            ->fields([
                dcCore::app()->formNonce(false),
                (new Para())
                    ->items([
                        (new Password('pwd'))
                            ->size(20)
                            ->maxlength(255)
                            ->autocomplete('current-password')
                            ->label((new Label(
                                __('Your password:'),
                                Label::OUTSIDE_LABEL_BEFORE
                            ))),
                    ]),
                (new Para())
                    ->separator(' ')
                    ->items([
                        (new Submit('del'))
                            ->class('delete')
                            ->value(__('Delete this blog')),
                        (new Button('back'))
                            ->class(['go-back', 'reset', 'hidden-if-no-js'])
                            ->value(__('Cancel')),
                    ]),
                (new Hidden('blog_id', dcCore::app()->admin->blog_id)),
            ])->render();
        }

        dcPage::close();
    }
}

adminBlogDel::init();
adminBlogDel::process();
adminBlogDel::render();
