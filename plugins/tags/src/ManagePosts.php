<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module backend manage tag posts process.
 * @ingroup tags
 */
class ManagePosts extends Process
{
    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(($_REQUEST['m'] ?? 'tags') === 'tag_posts');
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::backend()->tag = $_REQUEST['tag'] ?? '';

        App::backend()->page        = empty($_GET['page']) ? 1 : max(1, (int) $_GET['page']);
        App::backend()->nb_per_page = 30;

        // Get posts

        $params               = [];
        $params['limit']      = [((App::backend()->page - 1) * App::backend()->nb_per_page), App::backend()->nb_per_page];
        $params['no_content'] = true;
        $params['meta_id']    = App::backend()->tag;
        $params['meta_type']  = 'tag';
        $params['post_type']  = '';

        App::backend()->posts     = null;
        App::backend()->post_list = null;

        try {
            App::backend()->posts     = App::meta()->getPostsByMeta($params);
            $counter                  = App::meta()->getPostsByMeta($params, true);
            App::backend()->post_list = new ListingPosts(App::backend()->posts, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        App::backend()->posts_actions_page = new BackendActions(
            App::backend()->url()->get('admin.plugin'),
            ['p' => My::id(), 'm' => 'tag_posts', 'tag' => App::backend()->tag]
        );

        App::backend()->posts_actions_page_rendered = null;
        if (App::backend()->posts_actions_page->process()) {
            App::backend()->posts_actions_page_rendered = true;

            return true;
        }

        if (isset($_POST['new_tag_id'])) {
            // Rename a tag

            $new_id = App::meta()::sanitizeMetaID($_POST['new_tag_id']);

            try {
                if (App::meta()->updateMeta(App::backend()->tag, $new_id, 'tag')) {
                    Notices::addSuccessNotice(__('Tag has been successfully renamed'));
                    My::redirect([
                        'm'   => 'tag_posts',
                        'tag' => $new_id,
                    ]);
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['delete']) && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            // Delete a tag

            try {
                App::meta()->delMeta(App::backend()->tag, 'tag');
                Notices::addSuccessNotice(__('Tag has been successfully removed'));
                My::redirect([
                    'm' => 'tags',
                ]);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (App::backend()->posts_actions_page_rendered) {
            App::backend()->posts_actions_page->render();

            return;
        }

        $this_url = App::backend()->getPageURL() . '&amp;m=tag_posts&amp;tag=' . rawurlencode(App::backend()->tag);

        Page::openModule(
            My::name(),
            My::cssLoad('style') .
            Page::jsLoad('js/_posts_list.js') .
            Page::jsJson('posts_tags_msg', [
                'confirm_tag_delete' => sprintf(__('Are you sure you want to remove tag: “%s”?'), Html::escapeHTML(App::backend()->tag)),
            ]) .
            My::jsLoad('posts') .
            Page::jsConfirmClose('tag_rename')
        );

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name())                                      => '',
                My::name()                                                                 => App::backend()->getPageURL() . '&amp;m=tags',
                __('Tag') . ' &ldquo;' . Html::escapeHTML(App::backend()->tag) . '&rdquo;' => '',
            ]
        ) .
        Notices::getNotices() .
        '<p><a class="back" href="' . App::backend()->getPageURL() . '&amp;m=tags">' . __('Back to tags list') . '</a></p>';

        if (!App::error()->flag()) {
            if (!App::backend()->posts?->isEmpty()) {
                // Remove tag
                $delete = '';
                if (!App::backend()->posts->isEmpty() && App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]), App::blog()->id())) {
                    $delete = (new Form('tag_delete'))
                        ->action($this_url)
                        ->method('post')
                        ->fields([
                            (new Para())
                                ->items([
                                    (new Submit('delete', __('Delete this tag')))
                                        ->class('delete'),
                                    ...My::hiddenFields(),
                                ]),
                        ])
                    ->render();
                }

                echo (new Div())
                    ->class(['tag-actions', 'vertical-separator'])
                    ->items([
                        (new Text('h3', Html::escapeHTML(App::backend()->tag))),
                        (new Form('tag_rename'))
                            ->action($this_url)
                            ->method('post')
                            ->fields([
                                (new Para())
                                    ->items([
                                        (new Input('new_tag_id'))
                                            ->value(Html::escapeHTML(App::backend()->tag))
                                            ->size(40)
                                            ->maxlength(255)
                                            ->label((new Label(__('Rename:'), Label::INSIDE_LABEL_BEFORE))->class('classic')),
                                        (new Submit('sub_new_tag_id', __('OK'))),
                                        ...My::hiddenFields(),
                                    ]),
                            ]),
                        (new Text(null, $delete)),
                    ])
                ->render();
            }

            // Show posts
            echo (new Text('h4', __('List of entries with this tag')))
                ->class('vertical-separator pretty-title')
            ->render();

            if (App::backend()->post_list) {
                $form = (new Form('form-entries'))
                    ->action(App::backend()->getPageURL())
                    ->method('post')
                    ->fields([
                        (new Text(null, '%s')), // List of posts will be rendered here
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())
                                    ->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Select('action'))
                                            ->items(App::backend()->posts_actions_page->getCombo())
                                            ->label((new Label(__('Selected entries action:'), Label::INSIDE_LABEL_BEFORE))->class('classic')),
                                        (new Submit('do_action', __('ok'))),
                                        ...My::hiddenFields([
                                            'post_type' => '',
                                            'm'         => 'tag_posts',
                                            'tag'       => App::backend()->tag,
                                        ]),
                                    ]),
                            ]),
                    ])
                ->render();

                App::backend()->post_list->display(
                    App::backend()->page,
                    App::backend()->nb_per_page,
                    $form
                );
            }
        }

        Page::helpBlock('tag_posts');
        Page::closeModule();
    }
}
