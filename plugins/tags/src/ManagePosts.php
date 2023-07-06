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
            dcCore::app()->admin->post_list = new ListingPosts(dcCore::app()->admin->posts, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        dcCore::app()->admin->posts_actions_page = new BackendActions(
            dcCore::app()->admin->url->get('admin.plugin'),
            ['p' => My::id(), 'm' => 'tag_posts', 'tag' => dcCore::app()->admin->tag]
        );

        dcCore::app()->admin->posts_actions_page_rendered = null;
        if (dcCore::app()->admin->posts_actions_page->process()) {
            dcCore::app()->admin->posts_actions_page_rendered = true;

            return true;
        }

        if (isset($_POST['new_tag_id'])) {
            // Rename a tag

            $new_id = dcMeta::sanitizeMetaID($_POST['new_tag_id']);

            try {
                if (dcCore::app()->meta->updateMeta(dcCore::app()->admin->tag, $new_id, 'tag')) {
                    Notices::addSuccessNotice(__('Tag has been successfully renamed'));
                    My::redirect([
                        'm'   => 'tag_posts',
                        'tag' => $new_id,
                    ]);
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST['delete']) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_PUBLISH,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            // Delete a tag

            try {
                dcCore::app()->meta->delMeta(dcCore::app()->admin->tag, 'tag');
                Notices::addSuccessNotice(__('Tag has been successfully removed'));
                My::redirect([
                    'm' => 'tags',
                ]);
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
        if (!self::status()) {
            return;
        }

        if (dcCore::app()->admin->posts_actions_page_rendered) {
            dcCore::app()->admin->posts_actions_page->render();

            return;
        }

        $this_url = dcCore::app()->admin->getPageURL() . '&amp;m=tag_posts&amp;tag=' . rawurlencode(dcCore::app()->admin->tag);

        Page::openModule(
            My::name(),
            My::cssLoad('style.css') .
            Page::jsLoad('js/_posts_list.js') .
            Page::jsJson('posts_tags_msg', [
                'confirm_tag_delete' => sprintf(__('Are you sure you want to remove tag: “%s”?'), Html::escapeHTML(dcCore::app()->admin->tag)),
            ]) .
            My::jsLoad('posts.js') .
            Page::jsConfirmClose('tag_rename')
        );

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name)                                      => '',
                My::name()                                                                       => dcCore::app()->admin->getPageURL() . '&amp;m=tags',
                __('Tag') . ' &ldquo;' . Html::escapeHTML(dcCore::app()->admin->tag) . '&rdquo;' => '',
            ]
        ) .
        Notices::getNotices() .
        '<p><a class="back" href="' . dcCore::app()->admin->getPageURL() . '&amp;m=tags">' . __('Back to tags list') . '</a></p>';

        if (!dcCore::app()->error->flag()) {
            if (!dcCore::app()->admin->posts->isEmpty()) {
                echo
                '<div class="tag-actions vertical-separator">' .
                '<h3>' . Html::escapeHTML(dcCore::app()->admin->tag) . '</h3>' .
                '<form action="' . $this_url . '" method="post" id="tag_rename">' .
                '<p><label for="new_tag_id" class="classic">' . __('Rename') . '</label> ' .
                form::field('new_tag_id', 20, 255, Html::escapeHTML(dcCore::app()->admin->tag)) .
                '<input type="submit" value="' . __('OK') . '" />' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
                dcCore::app()->formNonce() .
                '</p></form>';

                // Remove tag
                if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
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
            '<h4 class="vertical-separator pretty-title">' . sprintf(__('List of entries with the tag “%s”'), Html::escapeHTML(dcCore::app()->admin->tag)) . '</h4>';
            dcCore::app()->admin->post_list->display(
                dcCore::app()->admin->page,
                dcCore::app()->admin->nb_per_page,
                '<form action="' . dcCore::app()->admin->url->get('admin.plugin') . '" method="post" id="form-entries">' .
                '%s' .
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .
                '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
                form::combo('action', dcCore::app()->admin->posts_actions_page->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('OK') . '" /></p>' .
                form::hidden('post_type', '') .
                form::hidden('p', My::id()) .
                form::hidden('m', 'tag_posts') .
                form::hidden('tag', dcCore::app()->admin->tag) .
                dcCore::app()->formNonce() .
                '</div>' .
                '</form>'
            );
        }
        Page::helpBlock('tag_posts');

        Page::closeModule();
    }
}
