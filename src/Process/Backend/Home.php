<?php
/**
 * @since 2.27 Before as admin/index.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use dcBlog;
use dcCore;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Helper;
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Home extends Process
{
    public static function init(): bool
    {
        if (!defined('DC_CONTEXT_ADMIN')) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        if (!empty($_GET['default_blog'])) {
            try {
                Core::users()->setUserDefaultBlog(Core::auth()->userID(), Core::blog()->id);
                Core::backend()->url->redirect('admin.home');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        $disabled = dcCore::app()->plugins->disableDepModules();
        if (count($disabled)) {
            Notices::addWarningNotice(
                __('The following plugins have been disabled :') .
                '<ul><li>' . implode("</li>\n<li>", $disabled) . '</li></ul>',
                ['divtag' => true, 'with_ts' => false]
            );

            Core::backend()->url->redirect('admin.home');
            exit;
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        /**
         * @deprecated since 2.27 Use Core::backend()->url->redirect('admin.logout');
         */
        if (!empty($_GET['logout'])) {
            // Enable REST service if disabled, for next requests
            if (!dcCore::app()->rest->serveRestRequests()) {
                dcCore::app()->rest->enableRestServer(true);
            }
            // Kill admin session
            Core::backend()->killAdminSession();
            // Logout
            Core::backend()->url->redirect('admin.auth');
            exit;
        }

        // Plugin install
        Core::backend()->plugins_install = dcCore::app()->plugins->installModules();

        return true;
    }

    public static function render(): void
    {
        // Check dashboard module prefs
        if (!Core::auth()->user_prefs->dashboard->prefExists('doclinks')) {
            if (!Core::auth()->user_prefs->dashboard->prefExists('doclinks', true)) {
                Core::auth()->user_prefs->dashboard->put('doclinks', true, 'boolean', '', false, true);
            }
            Core::auth()->user_prefs->dashboard->put('doclinks', true, 'boolean');
        }
        if (!Core::auth()->user_prefs->dashboard->prefExists('dcnews')) {
            if (!Core::auth()->user_prefs->dashboard->prefExists('dcnews', true)) {
                Core::auth()->user_prefs->dashboard->put('dcnews', true, 'boolean', '', false, true);
            }
            Core::auth()->user_prefs->dashboard->put('dcnews', true, 'boolean');
        }
        if (!Core::auth()->user_prefs->dashboard->prefExists('quickentry')) {
            if (!Core::auth()->user_prefs->dashboard->prefExists('quickentry', true)) {
                Core::auth()->user_prefs->dashboard->put('quickentry', false, 'boolean', '', false, true);
            }
            Core::auth()->user_prefs->dashboard->put('quickentry', false, 'boolean');
        }
        if (!Core::auth()->user_prefs->dashboard->prefExists('nodcupdate')) {
            if (!Core::auth()->user_prefs->dashboard->prefExists('nodcupdate', true)) {
                Core::auth()->user_prefs->dashboard->put('nodcupdate', false, 'boolean', '', false, true);
            }
            Core::auth()->user_prefs->dashboard->put('nodcupdate', false, 'boolean');
        }

        // Handle folded/unfolded sections in admin from user preferences
        if (!Core::auth()->user_prefs->toggles->prefExists('unfolded_sections')) {
            Core::auth()->user_prefs->toggles->put('unfolded_sections', '', 'string', 'Folded sections in admin', false, true);
        }

        // Dashboard icons
        $__dashboard_icons = new ArrayObject();
        Core::backend()->favs->appendDashboardIcons($__dashboard_icons);

        // Dashboard items
        $__dashboard_items = new ArrayObject([new ArrayObject(), new ArrayObject()]);
        $dashboardItem     = 0;

        // Documentation links
        if (Core::auth()->user_prefs->dashboard->doclinks && !empty(Core::backend()->resources->entries('doc'))) {
            $doc_links = '<div class="box small dc-box" id="doc-and-support"><h3>' . __('Documentation and support') . '</h3><ul>';

            foreach (Core::backend()->resources->entries('doc') as $k => $v) {
                $doc_links .= '<li><a class="outgoing" href="' . $v . '" title="' . $k . '">' . $k . ' <img src="images/outgoing-link.svg" alt="" /></a></li>';
            }

            $doc_links .= '</ul></div>';
            $__dashboard_items[$dashboardItem]->append($doc_links);
            $dashboardItem++;
        }

        # --BEHAVIOR-- adminDashboardItemsV2 -- ArrayObject
        Core::behavior()->callBehavior('adminDashboardItemsV2', $__dashboard_items);

        // Dashboard content
        $__dashboard_contents = new ArrayObject([new ArrayObject(), new ArrayObject()]);
        # --BEHAVIOR-- adminDashboardContentsV2 -- ArrayObject
        Core::behavior()->callBehavior('adminDashboardContentsV2', $__dashboard_contents);

        // Editor stuff
        $quickentry          = '';
        $admin_post_behavior = '';
        if (Core::auth()->user_prefs->dashboard->quickentry) {
            if (Core::auth()->check(Core::auth()->makePermissions([
                Core::auth()::PERMISSION_USAGE,
                Core::auth()::PERMISSION_CONTENT_ADMIN,
            ]), Core::blog()->id)) {
                $post_format = Core::auth()->getOption('post_format');
                $post_editor = Core::auth()->getOption('editor');
                if ($post_editor && !empty($post_editor[$post_format])) {
                    # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                    $admin_post_behavior = Core::behavior()->callBehavior('adminPostEditor', $post_editor[$post_format], 'quickentry', ['#post_content'], $post_format);
                }
            }
            $quickentry = Page::jsJson('dotclear_quickentry', [
                'post_published' => dcBlog::POST_PUBLISHED,
                'post_pending'   => dcBlog::POST_PENDING,
            ]);
        }

        // Dashboard drag'n'drop switch for its elements
        $dragndrop      = '';
        $dragndrop_head = '';
        if (!Core::auth()->user_prefs->accessibility->nodragdrop) {
            $dragndrop_msg = [
                'dragndrop_off' => __('Dashboard area\'s drag and drop is disabled'),
                'dragndrop_on'  => __('Dashboard area\'s drag and drop is enabled'),
            ];
            $dragndrop_head = Page::jsJson('dotclear_dragndrop', $dragndrop_msg);
            $dragndrop      = '<input type="checkbox" id="dragndrop" class="sr-only" title="' . $dragndrop_msg['dragndrop_off'] . '" />' .
                '<label for="dragndrop">' .
                '<svg aria-hidden="true" focusable="false" class="dragndrop-svg">' .
                '<use xlink:href="images/dragndrop.svg#mask"></use>' .
                '</svg>' .
                '<span id="dragndrop-label" class="sr-only">' . $dragndrop_msg['dragndrop_off'] . '</span>' .
                '</label>';
        }

        Page::open(
            __('Dashboard'),
            Page::jsLoad('js/jquery/jquery-ui.custom.js') .
            Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            $quickentry .
            Page::jsLoad('js/_index.js') .
            $dragndrop_head .
            $admin_post_behavior .
            Page::jsAdsBlockCheck() .

            # --BEHAVIOR-- adminDashboardHeaders --
            Core::behavior()->callBehavior('adminDashboardHeaders'),
            Page::breadcrumb(
                [
                    __('Dashboard') . ' : ' . Html::escapeHTML(Core::blog()->name) => '',
                ],
                ['home_link' => false]
            )
        );

        if (Core::auth()->getInfo('user_default_blog') != Core::blog()->id && Core::auth()->getBlogCount() > 1) {
            echo
            '<p><a href="' . Core::backend()->url->get('admin.home', ['default_blog' => 1]) . '" class="button">' . __('Make this blog my default blog') . '</a></p>';
        }

        if (Core::blog()->status == dcBlog::BLOG_OFFLINE) {
            Notices::message(__('This blog is offline'), false);
        } elseif (Core::blog()->status == dcBlog::BLOG_REMOVED) {
            Notices::message(__('This blog is removed'), false);
        }

        if (!defined('DC_ADMIN_URL') || !DC_ADMIN_URL) {
            Notices::message(
                sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_URL') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.'),
                false
            );
        }

        if (!defined('DC_ADMIN_MAILFROM') || !strpos(DC_ADMIN_MAILFROM, '@')) {
            Notices::message(
                sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_MAILFROM') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.'),
                false
            );
        }

        $err = [];

        // Check cache directory
        if (Core::auth()->isSuperAdmin()) {
            if (!is_dir(DC_TPL_CACHE) || !is_writable(DC_TPL_CACHE)) {
                $err[] = __('The cache directory does not exist or is not writable. You must create this directory with sufficient rights and affect this location to "DC_TPL_CACHE" in inc/config.php file.');
            }
        } else {
            if (!is_dir(DC_TPL_CACHE) || !is_writable(DC_TPL_CACHE)) {
                $err[] = __('The cache directory does not exist or is not writable. You should contact your administrator.');
            }
        }

        // Check public directory
        if (Core::auth()->isSuperAdmin()) {
            if (!is_dir(Core::blog()->public_path) || !is_writable(Core::blog()->public_path)) {
                $err[] = __('There is no writable directory /public/ at the location set in about:config "public_path". You must create this directory with sufficient rights (or change this setting).');
            }
        } else {
            if (!is_dir(Core::blog()->public_path) || !is_writable(Core::blog()->public_path)) {
                $err[] = __('There is no writable root directory for the media manager. You should contact your administrator.');
            }
        }

        // Error list
        if (count($err)) {
            Notices::error(
                __('Error:') .
                '<ul><li>' . implode("</li>\n<li>", $err) . '</li></ul>',
                false,
                true
            );
            unset($err);
        }

        // Plugins install messages
        if (!empty(Core::backend()->plugins_install['success'])) {
            $success = [];
            foreach (Core::backend()->plugins_install['success'] as $k => $v) {
                $info      = implode(' - ', ModulesList::getSettingsUrls($k, true));
                $success[] = $k . ($info !== '' ? ' → ' . $info : '');
            }

            Notices::success(
                __('Following plugins have been installed:') .
                '<ul><li>' . implode("</li>\n<li>", $success) . '</li></ul>',
                false,
                true
            );
            unset($success);
        }
        if (!empty(Core::backend()->plugins_install['failure'])) {
            $failure = [];
            foreach (Core::backend()->plugins_install['failure'] as $k => $v) {
                $failure[] = $k . ' (' . $v . ')';
            }

            Notices::error(
                __('Following plugins have not been installed:') .
                '<ul><li>' . implode("</li>\n<li>", $failure) . '</li></ul>',
                false,
                true
            );
            unset($failure);
        }

        // Errors modules notifications
        if (Core::auth()->isSuperAdmin()) {
            $list = dcCore::app()->plugins->getErrors();
            if (!empty($list)) {
                Notices::error(
                    __('Errors have occured with following plugins:') .
                    '<ul><li>' . implode("</li>\n<li>", $list) . '</li></ul>',
                    false,
                    true
                );
            }
        }

        // Get current main orders
        $main_order = Core::auth()->user_prefs->dashboard->main_order;
        $main_order = ($main_order != '' ? explode(',', $main_order) : []);

        // Get current boxes orders
        $boxes_order = Core::auth()->user_prefs->dashboard->boxes_order;
        $boxes_order = ($boxes_order != '' ? explode(',', $boxes_order) : []);

        // Get current boxes items orders
        $boxes_items_order = Core::auth()->user_prefs->dashboard->boxes_items_order;
        $boxes_items_order = ($boxes_items_order != '' ? explode(',', $boxes_items_order) : []);

        // Get current boxes contents orders
        $boxes_contents_order = Core::auth()->user_prefs->dashboard->boxes_contents_order;
        $boxes_contents_order = ($boxes_contents_order != '' ? explode(',', $boxes_contents_order) : []);

        $composeItems = function ($list, $blocks, $flat = false) {
            $ret   = [];
            $items = [];

            if ($flat) {
                $items = $blocks;
            } else {
                foreach ($blocks as $i) {
                    foreach ($i as $v) {
                        $items[] = $v;
                    }
                }
            }

            // First loop to find ordered indexes
            $order = [];
            $index = 0;
            foreach ($items as $v) {
                if (preg_match('/<div.*?id="([^"].*?)".*?>/ms', $v, $match)) {
                    $id       = $match[1];
                    $position = array_search($id, $list, true);
                    if ($position !== false) {
                        $order[$position] = $index;
                    }
                }
                $index++;
            }

            // Second loop to combine ordered items
            $index = 0;
            foreach ($items as $v) {
                $position = array_search($index, $order, true);
                if ($position !== false) {
                    $ret[$position] = $v;
                }
                $index++;
            }
            ksort($ret);    // Reorder items on their position (key)

            // Third loop to combine unordered items
            $index = 0;
            foreach ($items as $v) {
                $position = array_search($index, $order, true);
                if ($position === false) {
                    $ret[count($ret)] = $v;
                }
                $index++;
            }

            return join('', $ret);
        };

        // Compose dashboard items (doc, …)
        $dashboardItems = $composeItems($boxes_items_order, $__dashboard_items);

        // Compose dashboard contents (plugin's modules)
        $dashboardContents = $composeItems($boxes_contents_order, $__dashboard_contents);

        // Compose dashboard boxes (items, contents)
        $__dashboard_boxes = [];
        if ($dashboardItems != '') {
            $__dashboard_boxes[] = '<div class="db-items" id="db-items">' . $dashboardItems . '</div>';
        }
        if ($dashboardContents != '') {
            $__dashboard_boxes[] = '<div class="db-contents" id="db-contents">' . $dashboardContents . '</div>';
        }
        $dashboardBoxes = $composeItems($boxes_order, $__dashboard_boxes, true);

        // Compose main area (icons, quick entry, boxes)
        $__dashboard_main = [];
        if (!Core::auth()->user_prefs->dashboard->nofavicons) {
            // Dashboard icons

            $dashboardIcons = '<div id="icons">';
            foreach ($__dashboard_icons as $k => $i) {
                $dashboardIcons .= '<p><a href="' . $i[1] . '" id="icon-process-' . $k . '-fav">' . Helper::adminIcon($i[2]) .
            '<br /><span class="db-icon-title">' . $i[0] . '</span></a></p>';
            }
            $dashboardIcons .= '</div>';
            $__dashboard_main[] = $dashboardIcons;
        }

        if (Core::auth()->user_prefs->dashboard->quickentry && Core::auth()->check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id)) {
            // Quick entry

            // Get categories
            $categories_combo = Combos::getCategoriesCombo(
                Core::blog()->getCategories([])
            );

            $__dashboard_main[] = '<div id="quick">' .
                '<h3>' . __('Quick post') . sprintf(' &rsaquo; %s', Core::formater()->getFormaterName(Core::auth()->getOption('post_format'))) . '</h3>' .
                '<form id="quick-entry" action="' . Core::backend()->url->get('admin.post') . '" method="post" class="fieldset">' .
                '<h4>' . __('New post') . '</h4>' .
                '<p class="col"><label for="post_title" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label>' .
                form::field('post_title', 20, 255, [
                    'class'      => 'maximal',
                    'extra_html' => 'required placeholder="' . __('Title') . '"',
                ]) .
                '</p>' .
                '<div class="area"><label class="required" ' .
                'for="post_content"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Content:') . '</label> ' .
                form::textarea('post_content', 50, 10, ['extra_html' => 'required placeholder="' . __('Content') . '"']) .
                '</div>' .
                '<p><label for="cat_id" class="classic">' . __('Category:') . '</label> ' .
                form::combo('cat_id', $categories_combo) . '</p>' .
                (Core::auth()->check(Core::auth()->makePermissions([
                    Core::auth()::PERMISSION_CATEGORIES,
                ]), Core::blog()->id)
                    ? '<div>' .
                    '<p id="new_cat" class="q-cat">' . __('Add a new category') . '</p>' .
                    '<p class="q-cat"><label for="new_cat_title">' . __('Title:') . '</label> ' .
                    form::field('new_cat_title', 30, 255) . '</p>' .
                    '<p class="q-cat"><label for="new_cat_parent">' . __('Parent:') . '</label> ' .
                    form::combo('new_cat_parent', $categories_combo) .
                    '</p>' .
                    '<p class="form-note info clear">' . __('This category will be created when you will save your post.') . '</p>' .
                    '</div>'
                    : '') .
                '<p><input type="submit" value="' . __('Save') . '" name="save" /> ' .
                (Core::auth()->check(Core::auth()->makePermissions([
                    Core::auth()::PERMISSION_PUBLISH,
                ]), Core::blog()->id)
                    ? '<input type="hidden" value="' . __('Save and publish') . '" name="save-publish" />'
                    : '') .
                Core::nonce()->getFormNonce() .
                form::hidden('post_status', dcBlog::POST_PENDING) .
                form::hidden('post_format', Core::auth()->getOption('post_format')) .
                form::hidden('post_excerpt', '') .
                form::hidden('post_lang', Core::auth()->getInfo('user_lang')) .
                form::hidden('post_notes', '') .
                '</p>' .
                '</form>' .
                '</div>';
        }
        if ($dashboardBoxes != '') {
            $__dashboard_main[] = '<div id="dashboard-boxes">' . $dashboardBoxes . '</div>';
        }

        $dashboardMain = $composeItems($main_order, $__dashboard_main, true);

        echo $dragndrop . '<div id="dashboard-main">' . $dashboardMain . '</div>';

        Page::helpBlock('core_dashboard');
        Page::close();
    }
}
