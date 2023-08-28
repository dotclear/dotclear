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

use dcCore;
use dcMeta;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
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

        Core::backend()->tag = $_REQUEST['tag'] ?? '';

        Core::backend()->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        Core::backend()->nb_per_page = 30;

        // Get posts

        $params               = [];
        $params['limit']      = [((Core::backend()->page - 1) * Core::backend()->nb_per_page), Core::backend()->nb_per_page];
        $params['no_content'] = true;
        $params['meta_id']    = Core::backend()->tag;
        $params['meta_type']  = 'tag';
        $params['post_type']  = '';

        Core::backend()->posts     = null;
        Core::backend()->post_list = null;

        try {
            Core::backend()->posts     = Core::meta()->getPostsByMeta($params);
            $counter                        = Core::meta()->getPostsByMeta($params, true);
            Core::backend()->post_list = new ListingPosts(Core::backend()->posts, $counter->f(0));
        } catch (Exception $e) {
            Core::error()->add($e->getMessage());
        }

        Core::backend()->posts_actions_page = new BackendActions(
            Core::backend()->url->get('admin.plugin'),
            ['p' => My::id(), 'm' => 'tag_posts', 'tag' => Core::backend()->tag]
        );

        Core::backend()->posts_actions_page_rendered = null;
        if (Core::backend()->posts_actions_page->process()) {
            Core::backend()->posts_actions_page_rendered = true;

            return true;
        }

        if (isset($_POST['new_tag_id'])) {
            // Rename a tag

            $new_id = dcMeta::sanitizeMetaID($_POST['new_tag_id']);

            try {
                if (Core::meta()->updateMeta(Core::backend()->tag, $new_id, 'tag')) {
                    Notices::addSuccessNotice(__('Tag has been successfully renamed'));
                    My::redirect([
                        'm'   => 'tag_posts',
                        'tag' => $new_id,
                    ]);
                }
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['delete']) && Core::auth()->check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_PUBLISH,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id)) {
            // Delete a tag

            try {
                Core::meta()->delMeta(Core::backend()->tag, 'tag');
                Notices::addSuccessNotice(__('Tag has been successfully removed'));
                My::redirect([
                    'm' => 'tags',
                ]);
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
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

        if (Core::backend()->posts_actions_page_rendered) {
            Core::backend()->posts_actions_page->render();

            return;
        }

        $this_url = Core::backend()->getPageURL() . '&amp;m=tag_posts&amp;tag=' . rawurlencode(Core::backend()->tag);

        Page::openModule(
            My::name(),
            My::cssLoad('style') .
            Page::jsLoad('js/_posts_list.js') .
            Page::jsJson('posts_tags_msg', [
                'confirm_tag_delete' => sprintf(__('Are you sure you want to remove tag: “%s”?'), Html::escapeHTML(Core::backend()->tag)),
            ]) .
            My::jsLoad('posts') .
            Page::jsConfirmClose('tag_rename')
        );

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(Core::blog()->name)                                      => '',
                My::name()                                                                       => Core::backend()->getPageURL() . '&amp;m=tags',
                __('Tag') . ' &ldquo;' . Html::escapeHTML(Core::backend()->tag) . '&rdquo;' => '',
            ]
        ) .
        Notices::getNotices() .
        '<p><a class="back" href="' . Core::backend()->getPageURL() . '&amp;m=tags">' . __('Back to tags list') . '</a></p>';

        if (!Core::error()->flag()) {
            if (!Core::backend()->posts->isEmpty()) {
                echo
                '<div class="tag-actions vertical-separator">' .
                '<h3>' . Html::escapeHTML(Core::backend()->tag) . '</h3>' .
                '<form action="' . $this_url . '" method="post" id="tag_rename">' .
                '<p><label for="new_tag_id" class="classic">' . __('Rename') . '</label> ' .
                form::field('new_tag_id', 20, 255, Html::escapeHTML(Core::backend()->tag)) .
                '<input type="submit" value="' . __('OK') . '" />' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                Core::nonce()->getFormNonce() .
                '</p></form>';

                // Remove tag
                if (Core::auth()->check(Core::auth()->makePermissions([
                    Core::auth()::PERMISSION_CONTENT_ADMIN,
                ]), Core::blog()->id)) {
                    echo
                    '<form id="tag_delete" action="' . $this_url . '" method="post">' .
                    '<p><input type="submit" class="delete" name="delete" value="' . __('Delete this tag') . '" />' .
                    Core::nonce()->getFormNonce() .
                    '</p></form>';
                }

                echo
                '</div>';
            }

            // Show posts
            echo
            '<h4 class="vertical-separator pretty-title">' . sprintf(__('List of entries with the tag “%s”'), Html::escapeHTML(Core::backend()->tag)) . '</h4>';
            Core::backend()->post_list->display(
                Core::backend()->page,
                Core::backend()->nb_per_page,
                '<form action="' . Core::backend()->url->get('admin.plugin') . '" method="post" id="form-entries">' .
                '%s' .
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .
                '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
                form::combo('action', Core::backend()->posts_actions_page->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('OK') . '" /></p>' .
                form::hidden('post_type', '') .
                form::hidden('p', My::id()) .
                form::hidden('m', 'tag_posts') .
                form::hidden('tag', Core::backend()->tag) .
                Core::nonce()->getFormNonce() .
                '</div>' .
                '</form>'
            );
        }
        Page::helpBlock('tag_posts');

        Page::closeModule();
    }
}
