<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use ArrayObject;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\WikiToHtml;
use Exception;

/**
 * @brief   The module backend behaviors.
 * @ingroup tags
 */
class BackendBehaviors
{
    /**
     * Load tags specifics scripts.
     *
     * @param   string  $editor     The editor
     * @param   string  $context    The context
     */
    public static function adminPostEditor(string $editor = '', string $context = ''): string
    {
        if (($editor !== 'dcLegacyEditor' && $editor !== 'dcCKEditor') || $context !== 'post') {
            return '';
        }

        $tag_url = App::blog()->url() . App::url()->getURLFor('tag');

        if ($editor === 'dcLegacyEditor') {
            // dcLegacyEditor
            return
            Page::jsJson('legacy_editor_tags', [
                'tag' => [
                    'title' => __('Tag'),
                    'icon'  => My::fileURL('/img/tag-add.svg'),
                    'url'   => $tag_url,
                ],
            ]) .
            My::jsLoad('legacy-post');
        }

        // dcCKEditor
        return
        Page::jsJson('ck_editor_tags', [
            'tag_title' => __('Tag'),
            'tag_url'   => $tag_url,
        ]);
    }

    /**
     * Add tags CKEditor plugin.
     *
     * @param   ArrayObject<int, array<string, mixed>>      $extraPlugins   The extra plugins
     * @param   string                                      $context        The context
     */
    public static function ckeditorExtraPlugins(ArrayObject $extraPlugins, string $context): string
    {
        if ($context !== 'post') {
            return '';
        }
        $extraPlugins->append([
            'name'   => 'dctags',
            'button' => 'dcTags',
            'url'    => App::config()->adminUrl() . My::fileURL('js/ckeditor-tags-plugin.js'),
        ]);

        return '';
    }

    /**
     * Add an tags help ID if necessary.
     *
     * @param   ArrayObject<int, string>     $blocks     The blocks
     */
    public static function adminPageHelpBlock(ArrayObject $blocks): string
    {
        if (in_array('core_post', $blocks->getArrayCopy(), true)) {
            $blocks->append('tag_post');
        }

        return '';
    }

    /**
     * Add tags as dashboard favorites.
     *
     * @param   Favorites   $favs   The favs
     */
    public static function dashboardFavorites(Favorites $favs): string
    {
        $favs->register(My::id(), [
            'title'       => My::name(),
            'url'         => My::manageUrl(['m' => 'tags']),
            'small-icon'  => My::icons(),
            'large-icon'  => My::icons(),
            'permissions' => App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
        ]);

        return '';
    }

    /**
     * Init wiki tag URL scheme (tag:).
     *
     * @param   WikiToHtml  $wiki   The wiki 2 HTML
     */
    public static function coreInitWikiPost(WikiToHtml $wiki): string
    {
        $wiki->registerFunction('url:tag', BackendBehaviors::wikiTag(...));

        return '';
    }

    /**
     * Transform a tag wiki URL.
     *
     * @param   string  $url        The url
     * @param   string  $content    The content
     *
     * @return  array<string, string>
     */
    public static function wikiTag(string $url, string $content): array
    {
        $res = [];
        $url = substr($url, 4);
        if (str_starts_with($content, 'tag:')) {
            $content = substr($content, 4);
        }

        $tag_url        = Html::stripHostURL(App::blog()->url() . App::url()->getURLFor('tag'));
        $res['url']     = $tag_url . '/' . rawurlencode(App::meta()::sanitizeMetaID($url));
        $res['content'] = $content;

        return $res;
    }

    /**
     * Add tags fieldset in entry sidebar.
     *
     * @param   ArrayObject<string, mixed>      $main       The main part of the entry form
     * @param   ArrayObject<string, mixed>      $sidebar    The sidebar part of the entry form
     * @param   MetaRecord                      $post       The post
     */
    public static function tagsField(ArrayObject $main, ArrayObject $sidebar, ?MetaRecord $post): string
    {
        $meta = App::meta();

        if (!empty($_POST['post_tags'])) {
            $value = $_POST['post_tags'];
        } else {
            $value = $post instanceof MetaRecord ? $meta->getMetaStr($post->post_meta, 'tag') : '';
        }

        $sidebar['metas-box']['items']['post_tags'] = (new Para(null, 'h5'))
            ->items([
                (new Label(My::name(), Label::OUTSIDE_LABEL_BEFORE))
                    ->for('post_tags')
                    ->class('s-tags'),
            ])
            ->render() .
            (new Div('tags-edit'))
                ->class('p s-tags')
                ->items([
                    (new Textarea('post_tags', $value))
                        ->cols(20)
                        ->rows(3)
                        ->class('maximal'),
                ])
            ->render();

        return '';
    }

    /**
     * Store the tags of an entry.
     *
     * @param   Cursor  $cur        The current
     * @param   mixed   $post_id    The post identifier
     */
    public static function setTags(Cursor $cur, $post_id): string
    {
        $post_id = (int) $post_id;

        if (isset($_POST['post_tags'])) {
            $tags = $_POST['post_tags'];
            $meta = App::meta();
            $meta->delPostMeta($post_id, 'tag');

            foreach ($meta->splitMetaValues($tags) as $tag) {
                $meta->setPostMeta($post_id, 'tag', $tag);
            }
        }

        return '';
    }

    /**
     * Add tags actions.
     *
     * @param   ActionsPosts    $ap     The current action instance
     */
    public static function adminPostsActions(ActionsPosts $ap): void
    {
        $ap->addAction(
            [My::name() => [__('Add tags') => 'tags']],
            BackendBehaviors::adminAddTags(...)
        );

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [My::name() => [__('Remove tags') => 'tags_remove']],
                BackendBehaviors::adminRemoveTags(...)
            );
        }
    }

    /**
     * Add tags to an entry.
     *
     * @param   ActionsPosts                    $ap     The current action instance
     * @param   ArrayObject<string, mixed>      $post   The post
     */
    public static function adminAddTags(ActionsPosts $ap, ArrayObject $post): void
    {
        if (!empty($post['new_tags'])) {
            $meta  = App::meta();
            $tags  = $meta->splitMetaValues($post['new_tags']);
            $posts = $ap->getRS();
            while ($posts->fetch()) {
                # Get tags for post
                $post_meta = $meta->getMetadata([
                    'meta_type' => 'tag',
                    'post_id'   => $posts->post_id, ]);
                $pm = [];
                while ($post_meta->fetch()) {
                    $pm[] = $post_meta->meta_id;
                }
                foreach ($tags as $t) {
                    if (!in_array($t, $pm)) {
                        $meta->setPostMeta($posts->post_id, 'tag', $t);
                    }
                }
            }
            Notices::addSuccessNotice(
                __(
                    'Tag has been successfully added to selected entries',
                    'Tags have been successfully added to selected entries',
                    count($tags)
                )
            );
            $ap->redirect(true);
        } else {
            $opts = App::auth()->getOptions();
            $type = $opts['tag_list_format'] ?? 'more';

            $editor_tags_options = [
                'meta_url'            => App::backend()->url()->get('admin.plugin', ['p' => My::id(), 'm' => 'tag_posts']) . '&amp;tag=',
                'list_type'           => $type,
                'text_confirm_remove' => __('Are you sure you want to remove this tag?'),
                'text_add_meta'       => __('Add a tag to this entry'),
                'text_choose'         => __('Choose from list'),
                'text_all'            => __('all'),
                'text_separation'     => __('Enter tags separated by comma'),
            ];

            $ap->beginPage(
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name()) => '',
                        __('Entries')                         => $ap->getRedirection(true),
                        __('Add tags to this selection')      => '',
                    ]
                ),
                Page::jsMetaEditor() .
                Page::jsJson('editor_tags_options', $editor_tags_options) .
                Page::jsLoad('js/jquery/jquery.autocomplete.js') .
                My::jsLoad('posts_actions') .
                My::cssLoad('style')
            );

            echo (new Form('frm_new_tags'))
                ->action($ap->getURI())
                ->method('post')
                ->items([
                    $ap->checkboxes(),
                    (new Div())
                        ->items([
                            (new Textarea('new_tags'))
                                ->label(new Label(__('Tags to add:'), Label::INSIDE_LABEL_AFTER))
                                ->cols(60)
                                ->rows(3),
                        ]),
                    (new Para())
                        ->items([
                            ...$ap->hiddenFields(),
                            App::nonce()->formNonce(),
                            (new Hidden('action', 'tags')),
                            (new Submit(['save_tags'], __('Save'))),
                        ]),
                ])
            ->render();

            $ap->endPage();
        }
    }

    /**
     * Remove tags from an entry.
     *
     * @param   ActionsPosts                    $ap     The current action instance
     * @param   ArrayObject<string, mixed>      $post   The post
     */
    public static function adminRemoveTags(ActionsPosts $ap, ArrayObject $post): void
    {
        if (!empty($post['meta_id']) && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $meta  = App::meta();
            $posts = $ap->getRS();
            while ($posts->fetch()) {
                foreach ($_POST['meta_id'] as $v) {
                    $meta->delPostMeta($posts->post_id, 'tag', $v);
                }
            }
            Notices::addSuccessNotice(
                __(
                    'Tag has been successfully removed from selected entries',
                    'Tags have been successfully removed from selected entries',
                    is_countable($_POST['meta_id']) ? count($_POST['meta_id']) : 0
                )
            );
            $ap->redirect(true);
        } else {
            $meta = App::meta();
            $tags = [];

            foreach ($ap->getIDS() as $id) {
                $post_tags = $meta->getMetadata([
                    'meta_type' => 'tag',
                    'post_id'   => (int) $id, ])->toStatic()->rows();
                foreach ($post_tags as $v) {
                    if (isset($tags[$v['meta_id']])) {
                        $tags[$v['meta_id']]++;
                    } else {
                        $tags[$v['meta_id']] = 1;
                    }
                }
            }
            if ($tags === []) {
                throw new Exception(__('No tags for selected entries'));
            }
            $ap->beginPage(
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name())          => '',
                        __('Entries')                                  => App::backend()->url()->get('admin.posts'),
                        __('Remove selected tags from this selection') => '',
                    ]
                )
            );
            $posts_count = is_countable($_POST['entries']) ? count($_POST['entries']) : 0;

            $list = [];
            $i    = 0;
            foreach ($tags as $name => $number) {
                $label  = sprintf($posts_count == $number ? '<strong>%s</strong>' : '%s', Html::escapeHTML((string) $name));
                $list[] = (new Para())
                    ->items([
                        (new Checkbox(['meta_id[]','meta_id-' . ++$i]))
                            ->value(Html::escapeHTML((string) $name))
                            ->label(new Label($label, Label::INSIDE_TEXT_AFTER)),
                    ]);
            }

            echo (new Form('frm_rem_tags'))
                ->action($ap->getURI())
                ->method('post')
                ->items([
                    $ap->checkboxes(),
                    (new Div())
                        ->items([
                            (new Para())
                                ->items([
                                    (new Text(null, __('Following tags have been found in selected entries:'))),
                                ]),
                            ...$list,
                        ]),
                    (new Para())
                        ->items([
                            ...$ap->hiddenFields(),
                            App::nonce()->formNonce(),
                            (new Hidden('action', 'tags_remove')),
                            (new Submit(['rem_tags'], __('Remove'))),
                        ]),
                ])
            ->render();

            $ap->endPage();
        }
    }

    /**
     * Posts tags specific headers (scripts).
     */
    public static function postHeaders(): string
    {
        $opts = App::auth()->getOptions();
        $type = $opts['tag_list_format'] ?? 'more';

        $editor_tags_options = [
            'meta_url'            => App::backend()->url()->get('admin.plugin', ['p' => My::id(), 'm' => 'tag_posts']) . '&amp;tag=',
            'list_type'           => $type,
            'text_confirm_remove' => __('Are you sure you want to remove this tag?'),
            'text_add_meta'       => __('Add a tag to this entry'),
            'text_choose'         => __('Choose from list'),
            'text_all'            => __('all'),
            'text_separation'     => __('Enter tags separated by comma'),
        ];

        return
        Page::jsJson('editor_tags_options', $editor_tags_options) .
        Page::jsLoad('js/jquery/jquery.autocomplete.js') .
        My::jsLoad('post') .
        My::cssLoad('style');
    }

    /**
     * Admin user preferences tags fieldset.
     */
    public static function adminUserForm(): string
    {
        $opts = App::auth()->getOptions();

        $combo = [
            __('Short')    => 'more',
            __('Extended') => 'all',
        ];

        $value = array_key_exists('tag_list_format', $opts) ? $opts['tag_list_format'] : 'more';

        echo
        (new Fieldset('tags_prefs'))
            ->legend((new Legend(My::name())))
            ->fields([
                (new Para())
                    ->items([
                        (new Select('user_tag_list_format'))
                            ->label(new Label(__('Tags list format:'), Label::INSIDE_LABEL_BEFORE))
                            ->default($value)
                            ->items($combo),
                    ]),
            ])
        ->render();

        return '';
    }

    /**
     * Sets the tag list format.
     *
     * @param   Cursor          $cur        The current
     * @param   null|string     $user_id    The user identifier
     */
    public static function setTagListFormat(Cursor $cur, ?string $user_id = null): string
    {
        if (!is_null($user_id)) {
            $cur->user_options['tag_list_format'] = $_POST['user_tag_list_format'];
        }

        return '';
    }
}
