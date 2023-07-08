<?php
/**
 * @since 2.27 Before as admin/blog_del.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcCore;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

class BlogDel extends Process
{
    private static string $blog_id   = '';
    private static string $blog_name = '';

    public static function init(): bool
    {
        Page::checkSuper();

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
                    self::$blog_id   = $rs->blog_id;
                    self::$blog_name = $rs->blog_name;
                }
            }
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!dcCore::app()->error->flag() && self::$blog_id && !empty($_POST['del'])) {
            // Delete the blog
            if (!dcCore::app()->auth->checkPassword($_POST['pwd'])) {
                dcCore::app()->error->add(__('Password verification failed'));
            } else {
                try {
                    dcCore::app()->delBlog(self::$blog_id);
                    Notices::addSuccessNotice(sprintf(__('Blog "%s" successfully deleted'), Html::escapeHTML(self::$blog_name)));

                    dcCore::app()->admin->url->redirect('admin.blogs');
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            __('Delete a blog'),
            '',
            Page::breadcrumb(
                [
                    __('System')        => '',
                    __('Blogs')         => dcCore::app()->admin->url->get('admin.blogs'),
                    __('Delete a blog') => '',
                ]
            )
        );

        if (!dcCore::app()->error->flag()) {
            $msg = '<strong>' . __('Warning') . '</strong></p><p>' . sprintf(
                __('You are about to delete the blog %s. Every entry, comment and category will be deleted.'),
                '<strong>' . self::$blog_id . ' (' . self::$blog_name . ')</strong>'
            );
            Notices::warning($msg, false, true);

            echo
            // Legend
            (new Para())
            ->items([
                (new Text())->text(__('Please give your password to confirm the blog deletion.')),
            ])->render() .
            // Form
            (new Form('form-del'))
            ->action(dcCore::app()->admin->url->get('admin.blog.del'))
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
                (new Hidden('blog_id', self::$blog_id)),
            ])->render();
        }

        Page::close();
    }
}
