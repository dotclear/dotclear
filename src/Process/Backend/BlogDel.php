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

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
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

/**
 * @since 2.27 Before as admin/blog_del.php
 */
class BlogDel extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        App::backend()->blog_id   = '';
        App::backend()->blog_name = '';

        if (!empty($_POST['blog_id'])) {
            $rs = null;

            try {
                $rs = App::blogs()->getBlog($_POST['blog_id']);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            if ($rs instanceof MetaRecord) {
                if ($rs->isEmpty()) {
                    App::error()->add(__('No such blog ID'));
                } else {
                    App::backend()->blog_id   = $rs->blog_id;
                    App::backend()->blog_name = $rs->blog_name;
                }
            }
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!App::error()->flag() && App::backend()->blog_id && !empty($_POST['del'])) {
            // Delete the blog
            if (!App::auth()->checkPassword($_POST['pwd'])) {
                App::error()->add(__('Password verification failed'));
            } else {
                try {
                    App::blogs()->delBlog(App::backend()->blog_id);
                    Notices::addSuccessNotice(sprintf(__('Blog "%s" successfully deleted'), Html::escapeHTML(App::backend()->blog_name)));

                    App::backend()->url()->redirect('admin.blogs');
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
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
                    __('Blogs')         => App::backend()->url()->get('admin.blogs'),
                    __('Delete a blog') => '',
                ]
            )
        );

        if (!App::error()->flag()) {
            $msg = '<strong>' . __('Warning') . '</strong></p><p>' . sprintf(
                __('You are about to delete the blog %s. Every entry, comment and category will be deleted.'),
                '<strong>' . App::backend()->blog_id . ' (' . App::backend()->blog_name . ')</strong>'
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
            ->action(App::backend()->url()->get('admin.blog.del'))
            ->method('post')
            ->fields([
                App::nonce()->formNonce(),
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
                            ->value(__('Back')),
                    ]),
                (new Hidden('blog_id', App::backend()->blog_id)),
            ])->render();
        }

        Page::close();
    }
}
