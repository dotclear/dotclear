<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class tagsBehaviors
{
    /**
     * Load tags specifics scripts
     *
     * @param      string  $editor   The editor
     * @param      string  $context  The context
     *
     * @return     string  ( description_of_the_return_value )
     */
    public static function adminPostEditor(string $editor = '', string $context = ''): string
    {
        if (($editor !== 'dcLegacyEditor' && $editor !== 'dcCKEditor') || $context !== 'post') {
            return '';
        }

        $tag_url = dcCore::app()->blog->url . dcCore::app()->url->getURLFor('tag');

        if ($editor === 'dcLegacyEditor') {
            return
            dcPage::jsJson('legacy_editor_tags', [
                'tag' => [
                    'title' => __('Tag'),
                    'url'   => $tag_url,
                ],
            ]) .
            dcPage::jsModuleLoad('tags/js/legacy-post.js');
        } elseif ($editor === 'dcCKEditor') {
            return
            dcPage::jsJson('ck_editor_tags', [
                'tag_title' => __('Tag'),
                'tag_url'   => $tag_url,
            ]);
        }

        return '';
    }

    /**
     * Add tags CKEditor plugin
     *
     * @param      ArrayObject  $extraPlugins  The extra plugins
     * @param      string       $context       The context
     */
    public static function ckeditorExtraPlugins(ArrayObject $extraPlugins, string $context)
    {
        if ($context !== 'post') {
            return;
        }
        $extraPlugins[] = [
            'name'   => 'dctags',
            'button' => 'dcTags',
            'url'    => DC_ADMIN_URL . 'index.php?pf=tags/js/ckeditor-tags-plugin.js',
        ];
    }

    /**
     * Add an tags help ID if necessary
     *
     * @param      ArrayObject  $blocks  The blocks
     */
    public static function adminPageHelpBlock(ArrayObject $blocks): void
    {
        if (array_search('core_post', $blocks->getArrayCopy(), true) !== false) {
            $blocks->append('tag_post');
        }
    }

    /**
     * Add tags as dashboard favorites
     *
     * @param      dcFavorites  $favs   The favs
     */
    public static function dashboardFavorites(dcFavorites $favs): void
    {
        $favs->register('tags', [
            'title'       => __('Tags'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.tags', ['m' => 'tags']),
            'small-icon'  => [dcPage::getPF('tags/icon.svg'), dcPage::getPF('tags/icon-dark.svg')],
            'large-icon'  => [dcPage::getPF('tags/icon.svg'), dcPage::getPF('tags/icon-dark.svg')],
            'permissions' => dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]),
        ]);
    }

    /**
     * Init wiki tag URL scheme (tag:)
     *
     * @param      wiki2xhtml  $wiki2xhtml  The wiki 2 xhtml
     */
    public static function coreInitWikiPost(wiki2xhtml $wiki2xhtml): void
    {
        $wiki2xhtml->registerFunction('url:tag', ['tagsBehaviors', 'wiki2xhtmlTag']);
    }

    /**
     * Transform a tag wiki URL
     *
     * @param      string  $url      The url
     * @param      string  $content  The content
     *
     * @return     array   ( description_of_the_return_value )
     */
    public static function wiki2xhtmlTag(string $url, string $content): array
    {
        $url = substr($url, 4);
        if (strpos($content, 'tag:') === 0) {
            $content = substr($content, 4);
        }

        $tag_url        = html::stripHostURL(dcCore::app()->blog->url . dcCore::app()->url->getURLFor('tag'));
        $res['url']     = $tag_url . '/' . rawurlencode(dcMeta::sanitizeMetaID($url));
        $res['content'] = $content;

        return $res;
    }

    /**
     * Add tags fieldset in entry sidebar
     *
     * @param      ArrayObject  $main     The main part of the entry form
     * @param      ArrayObject  $sidebar  The sidebar part of the entry form
     * @param      dcRecord     $post     The post
     */
    public static function tagsField(ArrayObject $main, ArrayObject $sidebar, ?dcRecord $post): void
    {
        $meta = dcCore::app()->meta;

        if (!empty($_POST['post_tags'])) {
            $value = $_POST['post_tags'];
        } else {
            $value = ($post) ? $meta->getMetaStr($post->post_meta, 'tag') : '';
        }
        $sidebar['metas-box']['items']['post_tags'] = '<h5><label class="s-tags" for="post_tags">' . __('Tags') . '</label></h5>' .
        '<div class="p s-tags" id="tags-edit">' . form::textarea('post_tags', 20, 3, $value, 'maximal') . '</div>';
    }

    /**
     * Store the tags of an entry.
     *
     * @param      cursor  $cur      The current
     * @param      mixed   $post_id  The post identifier
     */
    public static function setTags(cursor $cur, $post_id): void
    {
        $post_id = (int) $post_id;

        if (isset($_POST['post_tags'])) {
            $tags = $_POST['post_tags'];
            $meta = dcCore::app()->meta;
            $meta->delPostMeta($post_id, 'tag');

            foreach ($meta->splitMetaValues($tags) as $tag) {
                $meta->setPostMeta($post_id, 'tag', $tag);
            }
        }
    }

    /**
     * Add tags actions
     *
     * @param      dcPostsActions       $ap     The current action instance
     */
    public static function adminPostsActionsPage(dcPostsActions $ap): void
    {
        $ap->addAction(
            [__('Tags') => [__('Add tags') => 'tags']],
            ['tagsBehaviors', 'adminAddTags']
        );

        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_DELETE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Tags') => [__('Remove tags') => 'tags_remove']],
                ['tagsBehaviors', 'adminRemoveTags']
            );
        }
    }

    /**
     * Add tags to an entry
     *
     * @param      dcPostsActions       $ap     The current action instance
     * @param      ArrayObject          $post   The post
     */
    public static function adminAddTags(dcPostsActions $ap, ArrayObject $post): void
    {
        if (!empty($post['new_tags'])) {
            $meta  = dcCore::app()->meta;
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
            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        'Tag has been successfully added to selected entries',
                        'Tags have been successfully added to selected entries',
                        count($tags)
                    )
                )
            );
            $ap->redirect(true);
        } else {
            $opts = dcCore::app()->auth->getOptions();
            $type = $opts['tag_list_format'] ?? 'more';

            $editor_tags_options = [
                'meta_url'            => 'plugin.php?p=tags&m=tag_posts&amp;tag=',
                'list_type'           => $type,
                'text_confirm_remove' => __('Are you sure you want to remove this tag?'),
                'text_add_meta'       => __('Add a tag to this entry'),
                'text_choose'         => __('Choose from list'),
                'text_all'            => __('all'),
                'text_separation'     => __('Enter tags separated by comma'),
            ];

            $msg = [
                'tags_autocomplete' => __('used in %e - frequency %p%'),
                'entry'             => __('entry'),
                'entries'           => __('entries'),
            ];

            $ap->beginPage(
                dcPage::breadcrumb(
                    [
                        html::escapeHTML(dcCore::app()->blog->name) => '',
                        __('Entries')                               => $ap->getRedirection(true),
                        __('Add tags to this selection')            => '',
                    ]
                ),
                dcPage::jsMetaEditor() .
                dcPage::jsJson('editor_tags_options', $editor_tags_options) .
                dcPage::jsJson('editor_tags_msg', $msg) .
                dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
                dcPage::jsModuleLoad('tags/js/posts_actions.js') .
                dcPage::cssModuleLoad('tags/style.css')
            );
            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<div><label for="new_tags" class="area">' . __('Tags to add:') . '</label> ' .
            form::textarea('new_tags', 60, 3) .
            '</div>' .
            dcCore::app()->formNonce() . $ap->getHiddenFields() .
            form::hidden(['action'], 'tags') .
            '<p><input type="submit" value="' . __('Save') . '" ' .
                'name="save_tags" /></p>' .
                '</form>';
            $ap->endPage();
        }
    }

    /**
     * Remove tags from an entry
     *
     * @param      dcPostsActions       $ap     The current action instance
     * @param      ArrayObject          $post   The post
     */
    public static function adminRemoveTags(dcPostsActions $ap, ArrayObject $post): void
    {
        if (!empty($post['meta_id']) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_DELETE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $meta  = dcCore::app()->meta;
            $posts = $ap->getRS();
            while ($posts->fetch()) {
                foreach ($_POST['meta_id'] as $v) {
                    $meta->delPostMeta($posts->post_id, 'tag', $v);
                }
            }
            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        'Tag has been successfully removed from selected entries',
                        'Tags have been successfully removed from selected entries',
                        count($_POST['meta_id'])
                    )
                )
            );
            $ap->redirect(true);
        } else {
            $meta = dcCore::app()->meta;
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
            if (empty($tags)) {
                throw new Exception(__('No tags for selected entries'));
            }
            $ap->beginPage(
                dcPage::breadcrumb(
                    [
                        html::escapeHTML(dcCore::app()->blog->name)    => '',
                        __('Entries')                                  => 'posts.php',
                        __('Remove selected tags from this selection') => '',
                    ]
                )
            );
            $posts_count = count($_POST['entries']);

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<div><p>' . __('Following tags have been found in selected entries:') . '</p>';

            foreach ($tags as $k => $n) {
                $label = '<label class="classic">%s %s</label>';
                if ($posts_count == $n) {
                    $label = sprintf($label, '%s', '<strong>%s</strong>');
                }
                echo '<p>' . sprintf(
                    $label,
                    form::checkbox(['meta_id[]'], html::escapeHTML($k)),
                    html::escapeHTML($k)
                ) .
                    '</p>';
            }

            echo
            '<p><input type="submit" value="' . __('ok') . '" />' .

            dcCore::app()->formNonce() . $ap->getHiddenFields() .
            form::hidden(['action'], 'tags_remove') .
                '</p></div></form>';
            $ap->endPage();
        }
    }

    /**
     * Posts tags specific headers (scripts).
     */
    public static function postHeaders(): string
    {
        $opts = dcCore::app()->auth->getOptions();
        $type = $opts['tag_list_format'] ?? 'more';

        $editor_tags_options = [
            'meta_url'            => 'plugin.php?p=tags&m=tag_posts&amp;tag=',
            'list_type'           => $type,
            'text_confirm_remove' => __('Are you sure you want to remove this tag?'),
            'text_add_meta'       => __('Add a tag to this entry'),
            'text_choose'         => __('Choose from list'),
            'text_all'            => __('all'),
            'text_separation'     => __('Enter tags separated by comma'),
        ];

        $msg = [
            'tags_autocomplete' => __('used in %e - frequency %p%'),
            'entry'             => __('entry'),
            'entries'           => __('entries'),
        ];

        return
        dcPage::jsJson('editor_tags_options', $editor_tags_options) .
        dcPage::jsJson('editor_tags_msg', $msg) .
        dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
        dcPage::jsModuleLoad('tags/js/post.js') .
        dcPage::cssModuleLoad('tags/style.css');
    }

    /**
     * Admin user preferences tags fieldset
     */
    public static function adminUserForm(): void
    {
        $opts = dcCore::app()->auth->getOptions();

        $combo = [
            __('Short')    => 'more',
            __('Extended') => 'all',
        ];

        $value = array_key_exists('tag_list_format', $opts) ? $opts['tag_list_format'] : 'more';

        echo
        '<div class="fieldset"><h5 id="tags_prefs">' . __('Tags') . '</h5>' .
        '<p><label for="user_tag_list_format" class="classic">' . __('Tags list format:') . '</label> ' .
        form::combo('user_tag_list_format', $combo, $value) .
        '</p></div>';
    }

    /**
     * Sets the tag list format.
     *
     * @param      cursor       $cur      The current
     * @param      null|string  $user_id  The user identifier
     */
    public static function setTagListFormat(cursor $cur, ?string $user_id = null)
    {
        if (!is_null($user_id)) {
            $cur->user_options['tag_list_format'] = $_POST['user_tag_list_format'];
        }
    }
}
