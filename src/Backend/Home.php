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

namespace Dotclear\Backend;

use adminModulesList;
use ArrayObject;
use dcAdminCombos;
use dcAdminHelper;
use dcAuth;
use dcBlog;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Home extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        if (!defined('DC_CONTEXT_ADMIN')) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        if (!empty($_GET['default_blog'])) {
            try {
                dcCore::app()->setUserDefaultBlog(dcCore::app()->auth->userID(), dcCore::app()->blog->id);
                dcCore::app()->adminurl->redirect('admin.home');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        if (dcCore::app()->plugins->disableDepModules(dcCore::app()->adminurl->get('admin.home', []))) {
            exit;
        }

        return true;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!empty($_GET['logout'])) {
            // Enable REST service if disabled, for next requests
            if (!dcCore::app()->serveRestRequests()) {
                dcCore::app()->enableRestServer(true);
            }
            // Kill admin session
            dcCore::app()->killAdminSession();
            // Logout
            dcCore::app()->adminurl->redirect('admin.auth');
            exit;
        }

        // Plugin install
        dcCore::app()->admin->plugins_install = dcCore::app()->plugins->installModules();

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        // Check dashboard module prefs
        if (!dcCore::app()->auth->user_prefs->dashboard->prefExists('doclinks')) {
            if (!dcCore::app()->auth->user_prefs->dashboard->prefExists('doclinks', true)) {
                dcCore::app()->auth->user_prefs->dashboard->put('doclinks', true, 'boolean', '', false, true);
            }
            dcCore::app()->auth->user_prefs->dashboard->put('doclinks', true, 'boolean');
        }
        if (!dcCore::app()->auth->user_prefs->dashboard->prefExists('dcnews')) {
            if (!dcCore::app()->auth->user_prefs->dashboard->prefExists('dcnews', true)) {
                dcCore::app()->auth->user_prefs->dashboard->put('dcnews', true, 'boolean', '', false, true);
            }
            dcCore::app()->auth->user_prefs->dashboard->put('dcnews', true, 'boolean');
        }
        if (!dcCore::app()->auth->user_prefs->dashboard->prefExists('quickentry')) {
            if (!dcCore::app()->auth->user_prefs->dashboard->prefExists('quickentry', true)) {
                dcCore::app()->auth->user_prefs->dashboard->put('quickentry', false, 'boolean', '', false, true);
            }
            dcCore::app()->auth->user_prefs->dashboard->put('quickentry', false, 'boolean');
        }
        if (!dcCore::app()->auth->user_prefs->dashboard->prefExists('nodcupdate')) {
            if (!dcCore::app()->auth->user_prefs->dashboard->prefExists('nodcupdate', true)) {
                dcCore::app()->auth->user_prefs->dashboard->put('nodcupdate', false, 'boolean', '', false, true);
            }
            dcCore::app()->auth->user_prefs->dashboard->put('nodcupdate', false, 'boolean');
        }

        // Handle folded/unfolded sections in admin from user preferences
        if (!dcCore::app()->auth->user_prefs->toggles->prefExists('unfolded_sections')) {
            dcCore::app()->auth->user_prefs->toggles->put('unfolded_sections', '', 'string', 'Folded sections in admin', false, true);
        }

        // Dashboard icons
        $__dashboard_icons = new ArrayObject();
        dcCore::app()->favs->appendDashboardIcons($__dashboard_icons);

        // Dashboard items
        $__dashboard_items = new ArrayObject([new ArrayObject(), new ArrayObject()]);
        $dashboardItem     = 0;

        // Documentation links
        if (dcCore::app()->auth->user_prefs->dashboard->doclinks && !empty(dcCore::app()->resources['doc'])) {
            $doc_links = '<div class="box small dc-box" id="doc-and-support"><h3>' . __('Documentation and support') . '</h3><ul>';

            foreach (dcCore::app()->resources['doc'] as $k => $v) {
                $doc_links .= '<li><a class="outgoing" href="' . $v . '" title="' . $k . '">' . $k . ' <img src="images/outgoing-link.svg" alt="" /></a></li>';
            }

            $doc_links .= '</ul></div>';
            $__dashboard_items[$dashboardItem]->append($doc_links);
            $dashboardItem++;
        }

        # --BEHAVIOR-- adminDashboardItemsV2 -- ArrayObject
        dcCore::app()->callBehavior('adminDashboardItemsV2', $__dashboard_items);

        // Dashboard content
        $__dashboard_contents = new ArrayObject([new ArrayObject(), new ArrayObject()]);
        # --BEHAVIOR-- adminDashboardContentsV2 -- ArrayObject
        dcCore::app()->callBehavior('adminDashboardContentsV2', $__dashboard_contents);

        // Editor stuff
        $quickentry          = '';
        $admin_post_behavior = '';
        if (dcCore::app()->auth->user_prefs->dashboard->quickentry) {
            if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)) {
                $post_format = dcCore::app()->auth->getOption('post_format');
                $post_editor = dcCore::app()->auth->getOption('editor');
                if ($post_editor && !empty($post_editor[$post_format])) {
                    # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                    $admin_post_behavior = dcCore::app()->callBehavior('adminPostEditor', $post_editor[$post_format], 'quickentry', ['#post_content'], $post_format);
                }
            }
            $quickentry = dcPage::jsJson('dotclear_quickentry', [
                'post_published' => dcBlog::POST_PUBLISHED,
                'post_pending'   => dcBlog::POST_PENDING,
            ]);
        }

        // Dashboard drag'n'drop switch for its elements
        $dragndrop      = '';
        $dragndrop_head = '';
        if (!dcCore::app()->auth->user_prefs->accessibility->nodragdrop) {
            $dragndrop_msg = [
                'dragndrop_off' => __('Dashboard area\'s drag and drop is disabled'),
                'dragndrop_on'  => __('Dashboard area\'s drag and drop is enabled'),
            ];
            $dragndrop_head = dcPage::jsJson('dotclear_dragndrop', $dragndrop_msg);
            $dragndrop      = '<input type="checkbox" id="dragndrop" class="sr-only" title="' . $dragndrop_msg['dragndrop_off'] . '" />' .
                '<label for="dragndrop">' .
                '<svg aria-hidden="true" focusable="false" class="dragndrop-svg">' .
                '<use xlink:href="images/dragndrop.svg#mask"></use>' .
                '</svg>' .
                '<span id="dragndrop-label" class="sr-only">' . $dragndrop_msg['dragndrop_off'] . '</span>' .
                '</label>';
        }

        dcPage::open(
            __('Dashboard'),
            dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
            dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            $quickentry .
            dcPage::jsLoad('js/_index.js') .
            $dragndrop_head .
            $admin_post_behavior .
            dcPage::jsAdsBlockCheck() .

            # --BEHAVIOR-- adminDashboardHeaders --
            dcCore::app()->callBehavior('adminDashboardHeaders'),
            dcPage::breadcrumb(
                [
                    __('Dashboard') . ' : ' . Html::escapeHTML(dcCore::app()->blog->name) => '',
                ],
                ['home_link' => false]
            )
        );

        if (dcCore::app()->auth->getInfo('user_default_blog') != dcCore::app()->blog->id && dcCore::app()->auth->getBlogCount() > 1) {
            echo
            '<p><a href="' . dcCore::app()->adminurl->get('admin.home', ['default_blog' => 1]) . '" class="button">' . __('Make this blog my default blog') . '</a></p>';
        }

        if (dcCore::app()->blog->status == dcBlog::BLOG_OFFLINE) {
            echo
            '<p class="static-msg">' . __('This blog is offline') . '.</p>';
        } elseif (dcCore::app()->blog->status == dcBlog::BLOG_REMOVED) {
            echo
            '<p class="static-msg">' . __('This blog is removed') . '.</p>';
        }

        if (!defined('DC_ADMIN_URL') || !DC_ADMIN_URL) {
            echo
            '<p class="static-msg">' .
            sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_URL') .
            ' ' . __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.') .
            '</p>';
        }

        if (!defined('DC_ADMIN_MAILFROM') || !strpos(DC_ADMIN_MAILFROM, '@')) {
            echo
            '<p class="static-msg">' .
            sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_MAILFROM') .
            ' ' . __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.') .
            '</p>';
        }

        $err = [];

        // Check cache directory
        if (dcCore::app()->auth->isSuperAdmin()) {
            if (!is_dir(DC_TPL_CACHE) || !is_writable(DC_TPL_CACHE)) {
                $err[] = '<p>' . __('The cache directory does not exist or is not writable. You must create this directory with sufficient rights and affect this location to "DC_TPL_CACHE" in inc/config.php file.') . '</p>';
            }
        } else {
            if (!is_dir(DC_TPL_CACHE) || !is_writable(DC_TPL_CACHE)) {
                $err[] = '<p>' . __('The cache directory does not exist or is not writable. You should contact your administrator.') . '</p>';
            }
        }

        // Check public directory
        if (dcCore::app()->auth->isSuperAdmin()) {
            if (!is_dir(dcCore::app()->blog->public_path) || !is_writable(dcCore::app()->blog->public_path)) {
                $err[] = '<p>' . __('There is no writable directory /public/ at the location set in about:config "public_path". You must create this directory with sufficient rights (or change this setting).') . '</p>';
            }
        } else {
            if (!is_dir(dcCore::app()->blog->public_path) || !is_writable(dcCore::app()->blog->public_path)) {
                $err[] = '<p>' . __('There is no writable root directory for the media manager. You should contact your administrator.') . '</p>';
            }
        }

        // Error list
        if (count($err)) {
            echo
            '<div class="error"><p><strong>' . __('Error:') . '</strong></p>' .
            '<ul><li>' . implode('</li><li>', $err) . '</li></ul></div>';
        }

        // Plugins install messages
        if (!empty(dcCore::app()->admin->plugins_install['success'])) {
            echo
            '<div class="success">' . __('Following plugins have been installed:') . '<ul>';
            foreach (dcCore::app()->admin->plugins_install['success'] as $k => $v) {
                $info = implode(' - ', adminModulesList::getSettingsUrls($k, true));
                echo
                '<li>' . $k . ($info !== '' ? ' → ' . $info : '') . '</li>';
            }
            echo
            '</ul></div>';
        }
        if (!empty(dcCore::app()->admin->plugins_install['failure'])) {
            echo
            '<div class="error">' . __('Following plugins have not been installed:') . '<ul>';
            foreach (dcCore::app()->admin->plugins_install['failure'] as $k => $v) {
                echo
                '<li>' . $k . ' (' . $v . ')</li>';
            }
            echo
            '</ul></div>';
        }

        // Errors modules notifications
        if (dcCore::app()->auth->isSuperAdmin()) {
            $list = dcCore::app()->plugins->getErrors();
            if (!empty($list)) {
                echo
                '<div class="error" id="module-errors" class="error"><p>' . __('Errors have occured with following plugins:') . '</p> ' .
                '<ul><li>' . implode("</li>\n<li>", $list) . '</li></ul></div>';
            }
        }

        // Get current main orders
        $main_order = dcCore::app()->auth->user_prefs->dashboard->main_order;
        $main_order = ($main_order != '' ? explode(',', $main_order) : []);

        // Get current boxes orders
        $boxes_order = dcCore::app()->auth->user_prefs->dashboard->boxes_order;
        $boxes_order = ($boxes_order != '' ? explode(',', $boxes_order) : []);

        // Get current boxes items orders
        $boxes_items_order = dcCore::app()->auth->user_prefs->dashboard->boxes_items_order;
        $boxes_items_order = ($boxes_items_order != '' ? explode(',', $boxes_items_order) : []);

        // Get current boxes contents orders
        $boxes_contents_order = dcCore::app()->auth->user_prefs->dashboard->boxes_contents_order;
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
        if (!dcCore::app()->auth->user_prefs->dashboard->nofavicons) {
            // Dashboard icons

            $dashboardIcons = '<div id="icons">';
            foreach ($__dashboard_icons as $k => $i) {
                $dashboardIcons .= '<p><a href="' . $i[1] . '" id="icon-process-' . $k . '-fav">' . dcAdminHelper::adminIcon($i[2]) .
            '<br /><span class="db-icon-title">' . $i[0] . '</span></a></p>';
            }
            $dashboardIcons .= '</div>';
            $__dashboard_main[] = $dashboardIcons;
        }

        if (dcCore::app()->auth->user_prefs->dashboard->quickentry && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            // Quick entry

            // Get categories
            $categories_combo = dcAdminCombos::getCategoriesCombo(
                dcCore::app()->blog->getCategories([])
            );

            $__dashboard_main[] = '<div id="quick">' .
                '<h3>' . __('Quick post') . sprintf(' &rsaquo; %s', dcCore::app()->getFormaterName(dcCore::app()->auth->getOption('post_format'))) . '</h3>' .
                '<form id="quick-entry" action="' . dcCore::app()->adminurl->get('admin.post') . '" method="post" class="fieldset">' .
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
                (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_CATEGORIES,
                ]), dcCore::app()->blog->id)
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
                (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_PUBLISH,
                ]), dcCore::app()->blog->id)
                    ? '<input type="hidden" value="' . __('Save and publish') . '" name="save-publish" />'
                    : '') .
                dcCore::app()->formNonce() .
                form::hidden('post_status', dcBlog::POST_PENDING) .
                form::hidden('post_format', dcCore::app()->auth->getOption('post_format')) .
                form::hidden('post_excerpt', '') .
                form::hidden('post_lang', dcCore::app()->auth->getInfo('user_lang')) .
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

        dcPage::helpBlock('core_dashboard');
        dcPage::close();
    }
}
