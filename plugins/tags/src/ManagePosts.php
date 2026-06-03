<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\App;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module backend manage tag posts process.
 * @ingroup tags
 */
class ManagePosts
{
    use TraitProcess;

    // Local static properties

    private static int $page;
    private static int $nb_per_page;
    private static string $tag;

    /**
     * Instance of backend actions
     */
    private static BackendActions $actions;

    /**
     * Have the current backend actions been rendered?
     */
    private static bool $actions_rendered;

    private static MetaRecord $posts;
    private static ListingPosts $post_list;

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

        self::$tag = isset($_REQUEST['tag']) && is_string($tag = $_REQUEST['tag']) ? $tag : '';

        self::$page        = isset($_GET['page']) && is_numeric($page = $_GET['page']) ? (int) $page : 1;
        self::$nb_per_page = 30;

        // Get posts

        $params               = [];
        $params['limit']      = [((self::$page - 1) * self::$nb_per_page), self::$nb_per_page];
        $params['no_content'] = true;
        $params['meta_id']    = self::$tag;
        $params['meta_type']  = 'tag';
        $params['post_type']  = '';

        try {
            self::$posts     = App::meta()->getPostsByMeta($params);
            $counter         = App::meta()->getPostsByMeta($params, true);
            self::$post_list = App::backend()->listing()->posts(self::$posts, $counter->cardinal());
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        self::$actions = new BackendActions(
            App::backend()->url()->get('admin.plugin'),
            ['p' => My::id(), 'm' => 'tag_posts', 'tag' => self::$tag, 'post_type' => '']
        );

        self::$actions_rendered = false;
        if (self::$actions->process()) {
            self::$actions_rendered = true;

            return true;
        }

        if (isset($_POST['new_tag_id']) && is_string($_POST['new_tag_id'])) {
            // Rename a tag

            $new_id = App::meta()::sanitizeMetaID($_POST['new_tag_id']);

            try {
                if (App::meta()->updateMeta(self::$tag, $new_id, 'tag')) {
                    App::backend()->notices()->addSuccessNotice(__('Tag has been successfully renamed'));
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
                App::meta()->delMeta(self::$tag, 'tag');
                App::backend()->notices()->addSuccessNotice(__('Tag has been successfully removed'));
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

        if (self::$actions_rendered) {
            self::$actions->render();

            return;
        }

        $this_url = App::backend()->getPageURL() . '&amp;m=tag_posts&amp;tag=' . rawurlencode(self::$tag);

        App::backend()->page()->openModule(
            My::name(),
            My::cssLoad('style') .
            App::backend()->page()->jsLoad('js/_posts_list.js') .
            App::backend()->page()->jsJson('posts_tags_msg', [
                'confirm_tag_delete' => sprintf(__('Are you sure you want to remove tag: “%s”?'), Html::escapeHTML(self::$tag)),
            ]) .
            My::jsLoad('posts') .
            App::backend()->page()->jsConfirmClose('tag_rename')
        );

        echo
        App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name())                             => '',
                My::name()                                                        => App::backend()->getPageURL() . '&amp;m=tags',
                __('Tag') . ' &ldquo;' . Html::escapeHTML(self::$tag) . '&rdquo;' => '',
            ]
        ) .
        App::backend()->notices()->getNotices() .
        '<p><a class="back" href="' . App::backend()->getPageURL() . '&amp;m=tags">' . __('Back to tags list') . '</a></p>';

        if (!App::error()->flag()) {
            if (isset(self::$posts) && !self::$posts->isEmpty()) {
                // Remove tag
                $delete = '';
                if (App::auth()->check(App::auth()->makePermissions([
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
                        (new Text('h3', Html::escapeHTML(self::$tag))),
                        (new Form('tag_rename'))
                            ->action($this_url)
                            ->method('post')
                            ->fields([
                                (new Para())
                                    ->items([
                                        (new Input('new_tag_id'))
                                            ->value(Html::escapeHTML(self::$tag))
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

            if (isset(self::$post_list)) {
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
                                            ->items(self::$actions->getCombo())
                                            ->label((new Label(__('Selected entries action:'), Label::INSIDE_LABEL_BEFORE))->class('classic')),
                                        (new Submit('do_action', __('ok'))),
                                        ...My::hiddenFields([
                                            'post_type' => '',
                                            'm'         => 'tag_posts',
                                            'tag'       => self::$tag,
                                        ]),
                                    ]),
                            ]),
                    ])
                ->render();

                self::$post_list->display(
                    self::$page,
                    self::$nb_per_page,
                    $form,
                    false,
                    true    // Display post type column
                );
            }
        }

        App::backend()->page()->helpBlock('tag_posts');
        App::backend()->page()->closeModule();
    }
}
