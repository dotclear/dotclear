<?php
/**
 * @since 2.27 Before as admin/blog.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcBlog;
use dcCore;
use dcSettings;
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Exception;

class Blog extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        dcCore::app()->admin->blog_id   = '';
        dcCore::app()->admin->blog_url  = '';
        dcCore::app()->admin->blog_name = '';
        dcCore::app()->admin->blog_desc = '';

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!isset($_POST['id']) && (isset($_POST['create']))) {
            // Create a blog
            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::BLOG_TABLE_NAME);

            dcCore::app()->admin->blog_id   = $cur->blog_id = $_POST['blog_id'];
            dcCore::app()->admin->blog_url  = $cur->blog_url = $_POST['blog_url'];
            dcCore::app()->admin->blog_name = $cur->blog_name = $_POST['blog_name'];
            dcCore::app()->admin->blog_desc = $cur->blog_desc = $_POST['blog_desc'];

            try {
                # --BEHAVIOR-- adminBeforeBlogCreate -- Cursor, string
                dcCore::app()->callBehavior('adminBeforeBlogCreate', $cur, dcCore::app()->admin->blog_id);

                dcCore::app()->addBlog($cur);

                # Default settings and override some
                $blog_settings = new dcSettings($cur->blog_id);
                $blog_settings->system->put('lang', dcCore::app()->auth->getInfo('user_lang'));
                $blog_settings->system->put('blog_timezone', dcCore::app()->auth->getInfo('user_tz'));

                if (substr(dcCore::app()->admin->blog_url, -1) == '?') {
                    $blog_settings->system->put('url_scan', 'query_string');
                } else {
                    $blog_settings->system->put('url_scan', 'path_info');
                }

                # --BEHAVIOR-- adminAfterBlogCreate -- Cursor, string, dcSettings
                dcCore::app()->callBehavior('adminAfterBlogCreate', $cur, dcCore::app()->admin->blog_id, $blog_settings);
                Notices::addSuccessNotice(sprintf(__('Blog "%s" successfully created'), Html::escapeHTML($cur->blog_name)));
                dcCore::app()->admin->url->redirect('admin.blog', ['id' => $cur->blog_id]);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!empty($_REQUEST['id'])) {
            dcCore::app()->admin->edit_blog_mode = true;
            App::process(BlogPref::class);
        } else {
            Page::open(
                __('New blog'),
                Page::jsConfirmClose('blog-form'),
                Page::breadcrumb(
                    [
                        __('System')   => '',
                        __('Blogs')    => dcCore::app()->admin->url->get('admin.blogs'),
                        __('New blog') => '',
                    ]
                )
            );

            echo
            // Form
            (new Form('blog-form'))
                ->action(dcCore::app()->admin->url->get('admin.blog'))
                ->method('post')
                ->fields([
                    // Form Nonce
                    dcCore::app()->formNonce(false),
                    // Blog ID
                    (new Para())
                        ->items([
                            (new Input('blog_id'))
                                ->size(30)
                                ->maxlength(32)
                                ->required(true)
                                ->placeholder(__('Blog ID'))
                                ->label(
                                    (new Label(
                                        '<abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog ID:'),
                                        Label::OUTSIDE_LABEL_BEFORE
                                    ))
                                    ->class('required')
                                ),
                        ]),
                    (new Note())
                        ->class('form-note')
                        ->text(__('At least 2 characters using letters, numbers or symbols.')),
                    // Blog name
                    (new Para())
                        ->items([
                            (new Input('blog_name'))
                                ->size(30)
                                ->maxlength(255)
                                ->required(true)
                                ->placeholder(__('Blog name'))
                                ->lang(dcCore::app()->auth->getInfo('user_lang'))
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        '<abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog name:'),
                                        Label::OUTSIDE_LABEL_BEFORE
                                    ))
                                    ->class('required')
                                ),
                        ]),
                    // Blog URL
                    (new Para())
                        ->items([
                            (new Url('blog_url'))
                                ->size(30)
                                ->maxlength(255)
                                ->required(true)
                                ->placeholder(__('Blog URL'))
                                ->label(
                                    (new Label(
                                        '<abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog URL:'),
                                        Label::OUTSIDE_LABEL_BEFORE
                                    ))
                                    ->class('required')
                                ),
                        ]),
                    // Blog description
                    (new Para())
                        ->class('area')
                        ->items([
                            (new Textarea('blog_desc'))
                                ->cols(60)
                                ->rows(5)
                                ->lang(dcCore::app()->auth->getInfo('user_lang'))
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        __('Blog description:'),
                                        Label::OUTSIDE_LABEL_BEFORE
                                    ))
                                ),
                        ]),
                    // Buttons
                    (new Para())
                        ->separator(' ')
                        ->items([
                            (new Submit(['create']))
                                ->accesskey('s')
                                ->value(__('Create')),
                            (new Button(['cancel']))
                                ->value(__('Cancel'))
                                ->class(['go-back', 'reset', 'hidden-if-no-js']),
                        ]),

                ])->render();

            Page::helpBlock('core_blog_new');
            Page::close();
        }
    }
}
