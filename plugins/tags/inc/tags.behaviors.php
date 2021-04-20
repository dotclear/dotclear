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

// BEHAVIORS
class tagsBehaviors
{
    public static function adminPostEditor($editor = '', $context = '', array $tags = [], $syntax = '')
    {
        if (($editor != 'dcLegacyEditor' && $editor != 'dcCKEditor') || $context != 'post') {
            return;
        }

        $tag_url = $GLOBALS['core']->blog->url . $GLOBALS['core']->url->getURLFor('tag');

        if ($editor == 'dcLegacyEditor') {
            return
            dcPage::jsJson('legacy_editor_tags', [
                'tag' => [
                    'title' => __('Tag'),
                    'url'   => $tag_url
                ]
            ]) .
            dcPage::jsLoad(dcPage::getPF('tags/js/legacy-post.js'));
        } elseif ($editor == 'dcCKEditor') {
            return
            dcPage::jsJson('ck_editor_tags', [
                'tag_title' => __('Tag'),
                'tag_url'   => $tag_url
            ]);
        }
    }

    public static function ckeditorExtraPlugins(ArrayObject $extraPlugins, $context)
    {
        global $core;

        if ($context != 'post') {
            return;
        }
        $extraPlugins[] = [
            'name'   => 'dctags',
            'button' => 'dcTags',
            'url'    => DC_ADMIN_URL . 'index.php?pf=tags/js/ckeditor-tags-plugin.js'
        ];
    }

    public static function adminPageHelpBlock($blocks)
    {
        $found = false;
        foreach ($blocks as $block) {
            if ($block == 'core_post') {
                $found = true;

                break;
            }
        }
        if (!$found) {
            return;
        }
        $blocks[] = 'tag_post';
    }

    public static function dashboardFavorites($core, $favs)
    {
        $favs->register('tags', [
            'title'       => __('Tags'),
            'url'         => $core->adminurl->get('admin.plugin.tags', ['m' => 'tags']),
            'small-icon'  => dcPage::getPF('tags/icon.png'),
            'large-icon'  => dcPage::getPF('tags/icon-big.png'),
            'permissions' => 'usage,contentadmin'
        ]);
    }

    public static function coreInitWikiPost($wiki2xhtml)
    {
        $wiki2xhtml->registerFunction('url:tag', ['tagsBehaviors', 'wiki2xhtmlTag']);
    }

    public static function wiki2xhtmlTag($url, $content)
    {
        $url = substr($url, 4);
        if (strpos($content, 'tag:') === 0) {
            $content = substr($content, 4);
        }

        $tag_url        = html::stripHostURL($GLOBALS['core']->blog->url . $GLOBALS['core']->url->getURLFor('tag'));
        $res['url']     = $tag_url . '/' . rawurlencode(dcMeta::sanitizeMetaID($url));
        $res['content'] = $content;

        return $res;
    }

    public static function tagsField($main, $sidebar, $post)
    {
        $meta = &$GLOBALS['core']->meta;

        if (!empty($_POST['post_tags'])) {
            $value = $_POST['post_tags'];
        } else {
            $value = ($post) ? $meta->getMetaStr($post->post_meta, 'tag') : '';
        }
        $sidebar['metas-box']['items']['post_tags'] = '<h5><label class="s-tags" for="post_tags">' . __('Tags') . '</label></h5>' .
        '<div class="p s-tags" id="tags-edit">' . form::textarea('post_tags', 20, 3, $value, 'maximal') . '</div>';
    }

    public static function setTags($cur, $post_id)
    {
        $post_id = (integer) $post_id;

        if (isset($_POST['post_tags'])) {
            $tags = $_POST['post_tags'];
            $meta = &$GLOBALS['core']->meta;
            $meta->delPostMeta($post_id, 'tag');

            foreach ($meta->splitMetaValues($tags) as $tag) {
                $meta->setPostMeta($post_id, 'tag', $tag);
            }
        }
    }

    public static function adminPostsActionsPage($core, $ap)
    {
        $ap->addAction(
            [__('Tags') => [__('Add tags') => 'tags']],
            ['tagsBehaviors', 'adminAddTags']
        );

        if ($core->auth->check('delete,contentadmin', $core->blog->id)) {
            $ap->addAction(
                [__('Tags') => [__('Remove tags') => 'tags_remove']],
                ['tagsBehaviors', 'adminRemoveTags']
            );
        }
    }

    public static function adminAddTags($core, dcPostsActionsPage $ap, $post)
    {
        if (!empty($post['new_tags'])) {
            $meta  = &$core->meta;
            $tags  = $meta->splitMetaValues($post['new_tags']);
            $posts = $ap->getRS();
            while ($posts->fetch()) {
                # Get tags for post
                $post_meta = $meta->getMetadata([
                    'meta_type' => 'tag',
                    'post_id'   => $posts->post_id]);
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
            dcPage::addSuccessNotice(sprintf(
                __(
                    'Tag has been successfully added to selected entries',
                    'Tags have been successfully added to selected entries',
                    count($tags))
            )
            );
            $ap->redirect(true);
        } else {
            $tag_url = $core->blog->url . $core->url->getURLFor('tag');

            $opts = $core->auth->getOptions();
            $type = $opts['tag_list_format'] ?? 'more';

            $editor_tags_options = [
                'meta_url'            => 'plugin.php?p=tags&m=tag_posts&amp;tag=',
                'list_type'           => $type,
                'text_confirm_remove' => __('Are you sure you want to remove this tag?'),
                'text_add_meta'       => __('Add a tag to this entry'),
                'text_choose'         => __('Choose from list'),
                'text_all'            => __('all'),
                'text_separation'     => __('Enter tags separated by comma')
            ];

            $msg = [
                'tags_autocomplete' => __('used in %e - frequency %p%'),
                'entry'             => __('entry'),
                'entries'           => __('entries')
            ];

            $ap->beginPage(
                dcPage::breadcrumb(
                    [
                        html::escapeHTML($core->blog->name) => '',
                        __('Entries')                       => $ap->getRedirection(true),
                        __('Add tags to this selection')    => ''
                    ]),
                dcPage::jsMetaEditor() .
                dcPage::jsJson('editor_tags_options', $editor_tags_options) .
                dcPage::jsJson('editor_tags_msg', $msg) .
                dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
                dcPage::jsLoad(dcPage::getPF('tags/js/posts_actions.js')) .
                dcPage::cssLoad(dcPage::getPF('tags/style.css'))
            );
            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<div><label for="new_tags" class="area">' . __('Tags to add:') . '</label> ' .
            form::textarea('new_tags', 60, 3) .
            '</div>' .
            $core->formNonce() . $ap->getHiddenFields() .
            form::hidden(['action'], 'tags') .
            '<p><input type="submit" value="' . __('Save') . '" ' .
                'name="save_tags" /></p>' .
                '</form>';
            $ap->endPage();
        }
    }
    public static function adminRemoveTags($core, dcPostsActionsPage $ap, $post)
    {
        if (!empty($post['meta_id']) && $core->auth->check('delete,contentadmin', $core->blog->id)) {
            $meta  = &$core->meta;
            $posts = $ap->getRS();
            while ($posts->fetch()) {
                foreach ($_POST['meta_id'] as $v) {
                    $meta->delPostMeta($posts->post_id, 'tag', $v);
                }
            }
            dcPage::addSuccessNotice(sprintf(
                __(
                    'Tag has been successfully removed from selected entries',
                    'Tags have been successfully removed from selected entries',
                    count($_POST['meta_id']))
            )
            );
            $ap->redirect(true);
        } else {
            $meta = &$core->meta;
            $tags = [];

            foreach ($ap->getIDS() as $id) {
                $post_tags = $meta->getMetadata([
                    'meta_type' => 'tag',
                    'post_id'   => (integer) $id])->toStatic()->rows();
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
                        html::escapeHTML($core->blog->name)            => '',
                        __('Entries')                                  => 'posts.php',
                        __('Remove selected tags from this selection') => ''
                    ]));
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
                echo '<p>' . sprintf($label,
                    form::checkbox(['meta_id[]'], html::escapeHTML($k)),
                    html::escapeHTML($k)) .
                    '</p>';
            }

            echo
            '<p><input type="submit" value="' . __('ok') . '" />' .

            $core->formNonce() . $ap->getHiddenFields() .
            form::hidden(['action'], 'tags_remove') .
                '</p></div></form>';
            $ap->endPage();
        }
    }

    public static function postHeaders()
    {
        $tag_url = $GLOBALS['core']->blog->url . $GLOBALS['core']->url->getURLFor('tag');

        $opts = $GLOBALS['core']->auth->getOptions();
        $type = $opts['tag_list_format'] ?? 'more';

        $editor_tags_options = [
            'meta_url'            => 'plugin.php?p=tags&m=tag_posts&amp;tag=',
            'list_type'           => $type,
            'text_confirm_remove' => __('Are you sure you want to remove this tag?'),
            'text_add_meta'       => __('Add a tag to this entry'),
            'text_choose'         => __('Choose from list'),
            'text_all'            => __('all'),
            'text_separation'     => __('Enter tags separated by comma')
        ];

        $msg = [
            'tags_autocomplete' => __('used in %e - frequency %p%'),
            'entry'             => __('entry'),
            'entries'           => __('entries')
        ];

        return
        dcPage::jsJson('editor_tags_options', $editor_tags_options) .
        dcPage::jsJson('editor_tags_msg', $msg) .
        dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
        dcPage::jsLoad(dcPage::getPF('tags/js/post.js')) .
        dcPage::cssLoad(dcPage::getPF('tags/style.css'));
    }

    public static function adminUserForm($args)
    {
        if ($args instanceof dcCore) {
            $opts = $args->auth->getOptions();
        } elseif ($args instanceof record) {
            $opts = $args->options();
        } else {
            $opts = [];
        }

        $combo                 = [];
        $combo[__('Short')]    = 'more';
        $combo[__('Extended')] = 'all';

        $value = array_key_exists('tag_list_format', $opts) ? $opts['tag_list_format'] : 'more';

        echo
        '<div class="fieldset"><h5 id="tags_prefs">' . __('Tags') . '</h5>' .
        '<p><label for="user_tag_list_format" class="classic">' . __('Tags list format:') . '</label> ' .
        form::combo('user_tag_list_format', $combo, $value) .
            '</p></div>';
    }

    public static function setTagListFormat($cur, $user_id = null)
    {
        if (!is_null($user_id)) {
            $cur->user_options['tag_list_format'] = $_POST['user_tag_list_format'];
        }
    }
}
