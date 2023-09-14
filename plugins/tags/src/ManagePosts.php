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
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

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

        App::backend()->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
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
            App::backend()->url->get('admin.plugin'),
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

    /**
     * Renders the page.
     */
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
            if (!App::backend()->posts->isEmpty()) {
                echo
                '<div class="tag-actions vertical-separator">' .
                '<h3>' . Html::escapeHTML(App::backend()->tag) . '</h3>' .
                '<form action="' . $this_url . '" method="post" id="tag_rename">' .
                '<p><label for="new_tag_id" class="classic">' . __('Rename') . '</label> ' .
                form::field('new_tag_id', 20, 255, Html::escapeHTML(App::backend()->tag)) .
                '<input type="submit" value="' . __('OK') . '" />' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                App::nonce()->getFormNonce() .
                '</p></form>';

                // Remove tag
                if (App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]), App::blog()->id())) {
                    echo
                    '<form id="tag_delete" action="' . $this_url . '" method="post">' .
                    '<p><input type="submit" class="delete" name="delete" value="' . __('Delete this tag') . '" />' .
                    App::nonce()->getFormNonce() .
                    '</p></form>';
                }

                echo
                '</div>';
            }

            // Show posts
            echo
            '<h4 class="vertical-separator pretty-title">' . sprintf(__('List of entries with the tag “%s”'), Html::escapeHTML(App::backend()->tag)) . '</h4>';
            App::backend()->post_list->display(
                App::backend()->page,
                App::backend()->nb_per_page,
                '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post" id="form-entries">' .
                '%s' .
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .
                '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
                form::combo('action', App::backend()->posts_actions_page->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('OK') . '" /></p>' .
                form::hidden('post_type', '') .
                form::hidden('p', My::id()) .
                form::hidden('m', 'tag_posts') .
                form::hidden('tag', App::backend()->tag) .
                App::nonce()->getFormNonce() .
                '</div>' .
                '</form>'
            );
        }
        Page::helpBlock('tag_posts');

        Page::closeModule();
    }
}
