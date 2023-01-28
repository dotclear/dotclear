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

use adminPostList;
use dcAuth;
use dcCore;
use dcMeta;
use dcNsProcess;
use dcPage;
use dcPostsActions;
use Exception;
use form;
use html;
use http;

class ManagePosts extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$init = ($_REQUEST['m'] ?? 'tags') === 'tag_posts';
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->admin->tag = $_REQUEST['tag'] ?? '';

        dcCore::app()->admin->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        dcCore::app()->admin->nb_per_page = 30;

        // Get posts

        $params               = [];
        $params['limit']      = [((dcCore::app()->admin->page - 1) * dcCore::app()->admin->nb_per_page), dcCore::app()->admin->nb_per_page];
        $params['no_content'] = true;
        $params['meta_id']    = dcCore::app()->admin->tag;
        $params['meta_type']  = 'tag';
        $params['post_type']  = '';

        dcCore::app()->admin->posts     = null;
        dcCore::app()->admin->post_list = null;

        try {
            dcCore::app()->admin->posts     = dcCore::app()->meta->getPostsByMeta($params);
            $counter                        = dcCore::app()->meta->getPostsByMeta($params, true);
            dcCore::app()->admin->post_list = new adminPostList(dcCore::app()->admin->posts, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        dcCore::app()->admin->posts_actions_page = new dcPostsActions(
            'plugin.php',
            ['p' => 'tags', 'm' => 'tag_posts', 'tag' => dcCore::app()->admin->tag]
        );

        if (dcCore::app()->admin->posts_actions_page->process()) {
            return false;
        }

        if (isset($_POST['new_tag_id'])) {
            // Rename a tag

            $new_id = dcMeta::sanitizeMetaID($_POST['new_tag_id']);

            try {
                if (dcCore::app()->meta->updateMeta(dcCore::app()->admin->tag, $new_id, 'tag')) {
                    dcPage::addSuccessNotice(__('Tag has been successfully renamed'));
                    http::redirect(dcCore::app()->admin->getPageURL() . '&m=tag_posts&tag=' . $new_id);
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST['delete']) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_PUBLISH,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            // Delete a tag

            try {
                dcCore::app()->meta->delMeta(dcCore::app()->admin->tag, 'tag');
                dcPage::addSuccessNotice(__('Tag has been successfully removed'));
                http::redirect(dcCore::app()->admin->getPageURL() . '&m=tags');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::$init) {
            return;
        }

        $this_url = dcCore::app()->admin->getPageURL() . '&amp;m=tag_posts&amp;tag=' . rawurlencode(dcCore::app()->admin->tag);

        echo
        '<html>' .
        '<head>' .
        '<title>' . __('Tags') . '</title>' .
        dcPage::cssModuleLoad('tags/css/style.css') .
        dcPage::jsLoad('js/_posts_list.js') .
        dcPage::jsJson('posts_tags_msg', [
            'confirm_tag_delete' => sprintf(__('Are you sure you want to remove tag: “%s”?'), html::escapeHTML(dcCore::app()->admin->tag)),
        ]) .
        dcPage::jsModuleLoad('tags/js/posts.js') .
        dcPage::jsConfirmClose('tag_rename') .
        '</head>' .
        '<body>' .
        dcPage::breadcrumb(
            [
                html::escapeHTML(dcCore::app()->blog->name)                                      => '',
                __('Tags')                                                                       => dcCore::app()->admin->getPageURL() . '&amp;m=tags',
                __('Tag') . ' &ldquo;' . html::escapeHTML(dcCore::app()->admin->tag) . '&rdquo;' => '',
            ]
        ) .
        dcPage::notices() .
        '<p><a class="back" href="' . dcCore::app()->admin->getPageURL() . '&amp;m=tags">' . __('Back to tags list') . '</a></p>';

        if (!dcCore::app()->error->flag()) {
            if (!dcCore::app()->admin->posts->isEmpty()) {
                echo
                '<div class="tag-actions vertical-separator">' .
                '<h3>' . html::escapeHTML(dcCore::app()->admin->tag) . '</h3>' .
                '<form action="' . $this_url . '" method="post" id="tag_rename">' .
                '<p><label for="new_tag_id" class="classic">' . __('Rename') . '</label> ' .
                form::field('new_tag_id', 20, 255, html::escapeHTML(dcCore::app()->admin->tag)) .
                '<input type="submit" value="' . __('OK') . '" />' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                dcCore::app()->formNonce() .
                '</p></form>';

                // Remove tag
                if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id)) {
                    echo
                    '<form id="tag_delete" action="' . $this_url . '" method="post">' .
                    '<p><input type="submit" class="delete" name="delete" value="' . __('Delete this tag') . '" />' .
                    dcCore::app()->formNonce() .
                    '</p></form>';
                }

                echo
                '</div>';
            }

            // Show posts
            echo
            '<h4 class="vertical-separator pretty-title">' . sprintf(__('List of entries with the tag “%s”'), html::escapeHTML(dcCore::app()->admin->tag)) . '</h4>';
            dcCore::app()->admin->post_list->display(
                dcCore::app()->admin->page,
                dcCore::app()->admin->nb_per_page,
                '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" id="form-entries">' .
                '%s' .
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .
                '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
                form::combo('action', dcCore::app()->admin->posts_actions_page->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('OK') . '" /></p>' .
                form::hidden('post_type', '') .
                form::hidden('p', 'tags') .
                form::hidden('m', 'tag_posts') .
                form::hidden('tag', dcCore::app()->admin->tag) .
                dcCore::app()->formNonce() .
                '</div>' .
                '</form>'
            );
        }
        dcPage::helpBlock('tag_posts');

        echo
        '</body>' .
        '</html>';
    }
}
