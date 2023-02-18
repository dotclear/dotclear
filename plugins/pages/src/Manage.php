<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use adminUserPref;
use dcAuth;
use dcCore;
use dcNsProcess;
use dcPage;
use Exception;
use form;
use html;
use initPages;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcPage::check(dcCore::app()->auth->makePermissions([
                initPages::PERMISSION_PAGES,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]));

            self::$init = ($_REQUEST['act'] ?? 'list') === 'page' ? ManagePage::init() : true;
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        if (($_REQUEST['act'] ?? 'list') === 'page') {
            return ManagePage::process();
        }

        $params = [
            'post_type' => 'page',
        ];

        dcCore::app()->admin->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        dcCore::app()->admin->nb_per_page = adminUserPref::getUserFilters('pages', 'nb');

        if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
            dcCore::app()->admin->nb_per_page = (int) $_GET['nb'];
        }

        $params['limit'] = [((dcCore::app()->admin->page - 1) * dcCore::app()->admin->nb_per_page), dcCore::app()->admin->nb_per_page];

        $params['no_content'] = true;
        $params['order']      = 'post_position ASC, post_title ASC';

        dcCore::app()->admin->post_list = null;

        try {
            $pages   = dcCore::app()->blog->getPosts($params);
            $counter = dcCore::app()->blog->getPosts($params, true);

            dcCore::app()->admin->post_list = new BackendList($pages, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        // Actions combo box
        dcCore::app()->admin->pages_actions_page          = new BackendActions('plugin.php', ['p' => 'pages']);
        dcCore::app()->admin->pages_actions_page_rendered = null;
        if (dcCore::app()->admin->pages_actions_page->process()) {
            dcCore::app()->admin->pages_actions_page_rendered = true;
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

        if (($_REQUEST['act'] ?? 'list') === 'page') {
            ManagePage::render();

            return;
        }

        if (dcCore::app()->admin->pages_actions_page_rendered) {
            dcCore::app()->admin->pages_actions_page->render();

            return;
        }

        echo
        '<html>' .
        '<head>' .
        '<title>' . __('Pages') . '</title>' .
        dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
        dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
        dcPage::jsJson('pages_list', ['confirm_delete_posts' => __('Are you sure you want to delete selected pages?')]) .
        dcPage::jsModuleLoad('pages/js/list.js') .
        '</head>' .
        '<body>' .
        dcPage::breadcrumb(
            [
                html::escapeHTML(dcCore::app()->blog->name) => '',
                __('Pages')                                 => '',
            ]
        ) .
        dcPage::notices();

        if (!empty($_GET['upd'])) {
            dcPage::success(__('Selected pages have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            dcPage::success(__('Selected pages have been successfully deleted.'));
        } elseif (!empty($_GET['reo'])) {
            dcPage::success(__('Selected pages have been successfully reordered.'));
        }
        echo
        '<p class="top-add"><a class="button add" href="' . dcCore::app()->admin->getPageURL() . '&amp;act=page">' . __('New page') . '</a></p>';

        if (!dcCore::app()->error->flag() && dcCore::app()->admin->post_list) {
            // Show pages
            dcCore::app()->admin->post_list->display(
                dcCore::app()->admin->page,
                dcCore::app()->admin->nb_per_page,
                '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" id="form-entries">' .

                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right"><label for="action" class="classic">' . __('Selected pages action:') . '</label> ' .
                form::combo('action', dcCore::app()->admin->pages_actions_page->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('ok') . '" />' .
                form::hidden(['post_type'], 'page') .
                form::hidden(['p'], 'pages') .
                form::hidden(['act'], 'list') .
                dcCore::app()->formNonce() .
                '</p></div>' .
                '<p class="clear form-note hidden-if-js">' .
                __('To rearrange pages order, change number at the begining of the line, then click on “Save pages order” button.') . '</p>' .
                '<p class="clear form-note hidden-if-no-js">' .
                __('To rearrange pages order, move items by drag and drop, then click on “Save pages order” button.') . '</p>' .
                '<p><input type="submit" value="' . __('Save pages order') . '" name="reorder" class="clear" /></p>' .
                '</form>'
            );
        }
        dcPage::helpBlock('pages');

        echo
        '</body>' .
        '</html>';
    }
}
