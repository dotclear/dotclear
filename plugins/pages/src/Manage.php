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

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Manage extends Process
{
    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(($_REQUEST['act'] ?? 'list') === 'page' ? ManagePage::init() : true);
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (($_REQUEST['act'] ?? 'list') === 'page') {
            return ManagePage::process();
        }

        $params = [
            'post_type' => 'page',
        ];

        Core::backend()->page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        Core::backend()->nb_per_page = UserPref::getUserFilters('pages', 'nb');

        if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
            Core::backend()->nb_per_page = (int) $_GET['nb'];
        }

        $params['limit'] = [((Core::backend()->page - 1) * Core::backend()->nb_per_page), Core::backend()->nb_per_page];

        $params['no_content'] = true;
        $params['order']      = 'post_position ASC, post_title ASC';

        Core::backend()->post_list = null;

        try {
            $pages   = Core::blog()->getPosts($params);
            $counter = Core::blog()->getPosts($params, true);

            Core::backend()->post_list = new BackendList($pages, $counter->f(0));
        } catch (Exception $e) {
            Core::error()->add($e->getMessage());
        }

        // Actions combo box
        Core::backend()->pages_actions_page          = new BackendActions(Core::backend()->url->get('admin.plugin'), ['p' => 'pages']);
        Core::backend()->pages_actions_page_rendered = null;
        if (Core::backend()->pages_actions_page->process()) {
            Core::backend()->pages_actions_page_rendered = true;
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

        if (($_REQUEST['act'] ?? 'list') === 'page') {
            ManagePage::render();

            return;
        }

        if (Core::backend()->pages_actions_page_rendered) {
            Core::backend()->pages_actions_page->render();

            return;
        }

        Page::openModule(
            __('Pages'),
            Page::jsLoad('js/jquery/jquery-ui.custom.js') .
            Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            Page::jsJson('pages_list', ['confirm_delete_posts' => __('Are you sure you want to delete selected pages?')]) .
            My::jsLoad('list')
        );

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(Core::blog()->name) => '',
                My::name()                           => '',
            ]
        ) .
        Notices::getNotices();

        if (!empty($_GET['upd'])) {
            Notices::success(__('Selected pages have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            Notices::success(__('Selected pages have been successfully deleted.'));
        } elseif (!empty($_GET['reo'])) {
            Notices::success(__('Selected pages have been successfully reordered.'));
        }
        echo
        '<p class="top-add"><a class="button add" href="' . Core::backend()->getPageURL() . '&amp;act=page">' . __('New page') . '</a></p>';

        if (!Core::error()->flag() && Core::backend()->post_list) {
            // Show pages
            Core::backend()->post_list->display(
                Core::backend()->page,
                Core::backend()->nb_per_page,
                '<form action="' . Core::backend()->url->get('admin.plugin') . '" method="post" id="form-entries">' .

                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right"><label for="action" class="classic">' . __('Selected pages action:') . '</label> ' .
                form::combo('action', Core::backend()->pages_actions_page->getCombo()) .
                '<input id="do-action" type="submit" value="' . __('ok') . '" />' .
                form::hidden(['post_type'], 'page') .
                form::hidden(['p'], My::id()) .
                form::hidden(['act'], 'list') .
                Core::nonce()->getFormNonce() .
                '</p></div>' .
                '<p class="clear form-note hidden-if-js">' .
                __('To rearrange pages order, change number at the begining of the line, then click on “Save pages order” button.') . '</p>' .
                '<p class="clear form-note hidden-if-no-js">' .
                __('To rearrange pages order, move items by drag and drop, then click on “Save pages order” button.') . '</p>' .
                '<p><input type="submit" value="' . __('Save pages order') . '" name="reorder" class="clear" /></p>' .
                '</form>'
            );
        }
        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
