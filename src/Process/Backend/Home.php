<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Helper;
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/index.php
 */
class Home extends Process
{
    public static function init(): bool
    {
        if (!App::task()->checkContext('BACKEND')) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        if (!empty($_GET['default_blog'])) {
            try {
                App::users()->setUserDefaultBlog((string) App::auth()->userID(), App::blog()->id());
                App::backend()->url()->redirect('admin.home');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        $disabled = App::plugins()->disableDepModules();
        if (count($disabled)) {
            Notices::addWarningNotice(
                __('The following plugins have been disabled :') .
                '<ul><li>' . implode("</li>\n<li>", $disabled) . '</li></ul>',
                ['divtag' => true, 'with_ts' => false]
            );

            App::backend()->url()->redirect('admin.home');
            exit;
        }

        return self::status(true);
    }

    /**
     * @deprecated  use of logout=1 in URL since 2.27, use App::backend()->url()->redirect('admin.logout'); instead
     */
    public static function process(): bool
    {
        if (!empty($_GET['logout'])) {
            // Enable REST service if disabled, for next requests
            if (!App::rest()->serveRestRequests()) {
                App::rest()->enableRestServer(true);
            }
            // Kill admin session
            App::backend()->killAdminSession();
            // Logout
            App::backend()->url()->redirect('admin.auth');
            exit;
        }

        // Plugin install
        App::backend()->plugins_install = App::plugins()->installModules();

        return true;
    }

    public static function render(): void
    {
        // Check dashboard module prefs
        if (!App::auth()->prefs()->dashboard->prefExists('doclinks')) {
            if (!App::auth()->prefs()->dashboard->prefExists('doclinks', true)) {
                App::auth()->prefs()->dashboard->put('doclinks', true, 'boolean', '', false, true);
            }
            App::auth()->prefs()->dashboard->put('doclinks', true, 'boolean');
        }
        if (!App::auth()->prefs()->dashboard->prefExists('donate')) {
            if (!App::auth()->prefs()->dashboard->prefExists('donate', true)) {
                App::auth()->prefs()->dashboard->put('donate', true, 'boolean', '', false, true);
            }
            App::auth()->prefs()->dashboard->put('donate', true, 'boolean');
        }
        if (!App::auth()->prefs()->dashboard->prefExists('dcnews')) {
            if (!App::auth()->prefs()->dashboard->prefExists('dcnews', true)) {
                App::auth()->prefs()->dashboard->put('dcnews', true, 'boolean', '', false, true);
            }
            App::auth()->prefs()->dashboard->put('dcnews', true, 'boolean');
        }
        if (!App::auth()->prefs()->dashboard->prefExists('quickentry')) {
            if (!App::auth()->prefs()->dashboard->prefExists('quickentry', true)) {
                App::auth()->prefs()->dashboard->put('quickentry', false, 'boolean', '', false, true);
            }
            App::auth()->prefs()->dashboard->put('quickentry', false, 'boolean');
        }
        if (!App::auth()->prefs()->dashboard->prefExists('nodcupdate')) {
            if (!App::auth()->prefs()->dashboard->prefExists('nodcupdate', true)) {
                App::auth()->prefs()->dashboard->put('nodcupdate', false, 'boolean', '', false, true);
            }
            App::auth()->prefs()->dashboard->put('nodcupdate', false, 'boolean');
        }

        // Handle folded/unfolded sections in admin from user preferences
        if (!App::auth()->prefs()->toggles->prefExists('unfolded_sections')) {
            App::auth()->prefs()->toggles->put('unfolded_sections', '', 'string', 'Folded sections in admin', false, true);
        }

        // Dashboard icons
        $__dashboard_icons = new ArrayObject();
        App::backend()->favorites()->appendDashboardIcons($__dashboard_icons);

        // Dashboard items
        $__dashboard_items = new ArrayObject([new ArrayObject(), new ArrayObject()]);
        $dashboardItem     = 0;

        // Documentation links
        if (App::auth()->prefs()->dashboard->doclinks && !empty(App::backend()->resources()->entries('doc'))) {
            $doc_links = '<div class="box small dc-box" id="doc-and-support"><h3>' . __('Documentation and support') . '</h3><ul>';

            foreach (App::backend()->resources()->entries('doc') as $k => $v) {
                $doc_links .= '<li><a href="' . $v . '" title="' . $k . '">' . $k . '</a></li>';
            }

            $doc_links .= '</ul></div>';
            $__dashboard_items[$dashboardItem]->append($doc_links); // @phpstan-ignore-line
        }

        // Call for donations
        if (App::auth()->prefs()->dashboard->donate) {
            $donation = '<div class="box small dc-box" id="donate">' .
                '<h3>' . __('Donate to Dotclear') . '</h3>' .
                '<p>' . __('Dotclear is not a commercial project — using Dotclear is <strong>free</strong> and <strong>always</strong> will be. If you wish to, you may contribute to Dotclear to help us cover project-related expenses.') . '</p>' .
                '<p>' . __('The collected funds will be spent as follows:') . '</p>' .
                '<ul>' .
                '<li>' . __('Paying for the website hosting and translations') . '</li>' .
                '<li>' . __('Paying for the domain names') . '</li>' .
                '<li>' . __('Supporting related projects such as Dotaddict.org') . '</li>' .
                '<li>' . __('Cover the costs of events set up by Dotclear') . '</li>' .
                '</ul>' .
                '<p>' . __('See <a href="https://dotclear.org/donate">this page</a> for more information and donation') . '</p>' .
                '</div>';
            $__dashboard_items[$dashboardItem]->append($donation); // @phpstan-ignore-line
        }

        # --BEHAVIOR-- adminDashboardItemsV2 -- ArrayObject
        App::behavior()->callBehavior('adminDashboardItemsV2', $__dashboard_items);

        // Dashboard content
        $__dashboard_contents = new ArrayObject([new ArrayObject(), new ArrayObject()]);
        # --BEHAVIOR-- adminDashboardContentsV2 -- ArrayObject
        App::behavior()->callBehavior('adminDashboardContentsV2', $__dashboard_contents);

        // Editor stuff
        $quickentry          = '';
        $admin_post_behavior = '';
        if (App::auth()->prefs()->dashboard->quickentry) {
            if (App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())) {
                $post_format = App::auth()->getOption('post_format');
                $post_editor = App::auth()->getOption('editor');
                if ($post_editor && !empty($post_editor[$post_format])) {
                    # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                    $admin_post_behavior = App::behavior()->callBehavior('adminPostEditor', $post_editor[$post_format], 'quickentry', ['#post_content'], $post_format);
                }
            }
            $quickentry = Page::jsJson('dotclear_quickentry', [
                'post_published' => App::blog()::POST_PUBLISHED,
                'post_pending'   => App::blog()::POST_PENDING,
            ]);
        }

        // Dashboard drag'n'drop switch for its elements
        $dragndrop      = '';
        $dragndrop_head = '';
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $dragndrop_msg = [
                'dragndrop_off' => __('Dashboard area\'s drag and drop is disabled'),
                'dragndrop_on'  => __('Dashboard area\'s drag and drop is enabled'),
            ];
            $dragndrop_head = Page::jsJson('dotclear_dragndrop', $dragndrop_msg);
            $dragndrop      = '<input type="checkbox" id="dragndrop" class="sr-only" title="' . $dragndrop_msg['dragndrop_off'] . '">' .
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
            App::behavior()->callBehavior('adminDashboardHeaders'),
            Page::breadcrumb(
                [
                    __('Dashboard') . ' : ' . '<span class="blog-title">' . Html::escapeHTML(App::blog()->name()) . '</span>' => '',
                ],
                ['home_link' => false]
            )
        );

        if (App::auth()->getInfo('user_default_blog') != App::blog()->id() && App::auth()->getBlogCount() > 1) {
            echo
            '<p><a href="' . App::backend()->url()->get('admin.home', ['default_blog' => 1]) . '" class="button">' . __('Make this blog my default blog') . '</a></p>';
        }

        if (App::blog()->status() == App::blog()::BLOG_OFFLINE) {
            Notices::message(__('This blog is offline'), false);
        } elseif (App::blog()->status() == App::blog()::BLOG_REMOVED) {
            Notices::message(__('This blog is removed'), false);
        }

        if (App::config()->adminUrl() == '') {
            Notices::message(
                sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_URL') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.'),
                false
            );
        }

        if (App::config()->adminMailfrom() == 'dotclear@local') {
            Notices::message(
                sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_MAILFROM') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.'),
                false
            );
        }

        $err = [];

        // Check cache directory
        if (App::auth()->isSuperAdmin()) {
            if (!is_dir(App::config()->cacheRoot()) || !is_writable(App::config()->cacheRoot())) {
                $err[] = __('The cache directory does not exist or is not writable. You must create this directory with sufficient rights and affect this location to "DC_TPL_CACHE" in inc/config.php file.');
            }
        } else {
            if (!is_dir(App::config()->cacheRoot()) || !is_writable(App::config()->cacheRoot())) {
                $err[] = __('The cache directory does not exist or is not writable. You should contact your administrator.');
            }
        }

        // Check public directory
        if (App::auth()->isSuperAdmin()) {
            if (!is_dir(App::blog()->publicPath()) || !is_writable(App::blog()->publicPath())) {
                $err[] = __('There is no writable directory /public/ at the location set in about:config "public_path". You must create this directory with sufficient rights (or change this setting).');
            }
        } else {
            if (!is_dir(App::blog()->publicPath()) || !is_writable(App::blog()->publicPath())) {
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
        if (!empty(App::backend()->plugins_install['success'])) {
            $success = [];
            foreach (App::backend()->plugins_install['success'] as $k => $v) {
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
        if (!empty(App::backend()->plugins_install['failure'])) {
            $failure = [];
            foreach (App::backend()->plugins_install['failure'] as $k => $v) {
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
        if (App::auth()->isSuperAdmin()) {
            $list = App::plugins()->getErrors();
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
        $main_order = App::auth()->prefs()->dashboard->main_order;
        $main_order = ($main_order != '' ? explode(',', $main_order) : []);

        // Get current boxes orders
        $boxes_order = App::auth()->prefs()->dashboard->boxes_order;
        $boxes_order = ($boxes_order != '' ? explode(',', $boxes_order) : []);

        // Get current boxes items orders
        $boxes_items_order = App::auth()->prefs()->dashboard->boxes_items_order;
        $boxes_items_order = ($boxes_items_order != '' ? explode(',', $boxes_items_order) : []);

        // Get current boxes contents orders
        $boxes_contents_order = App::auth()->prefs()->dashboard->boxes_contents_order;
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
                    $ret[] = $v;
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
        if (!App::auth()->prefs()->dashboard->nofavicons) {
            // Dashboard icons

            $dashboardIcons = '<div id="icons">';
            foreach ($__dashboard_icons as $k => $i) {
                $dashboardIcons .= '<p><a href="' . $i[1] . '" id="icon-process-' . $k . '-fav">' . Helper::adminIcon($i[2]) .
            '<br><span class="db-icon-title">' . $i[0] . '</span></a></p>';
            }
            $dashboardIcons .= '</div>';
            $__dashboard_main[] = $dashboardIcons;
        }

        if (App::auth()->prefs()->dashboard->quickentry && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            // Quick entry

            // Get categories
            $categories_combo = Combos::getCategoriesCombo(
                App::blog()->getCategories([])
            );

            $__dashboard_main[] = '<div id="quick">' .
                '<h3>' . __('Quick post') . sprintf(' &rsaquo; %s', App::formater()->getFormaterName(App::auth()->getOption('post_format'))) . '</h3>' .
                '<form id="quick-entry" action="' . App::backend()->url()->get('admin.post') . '" method="post" class="fieldset">' .
                '<h4>' . __('New post') . '</h4>' .
                '<p class="form-note">' . sprintf(__('Fields preceded by %s are mandatory.'), '<span class="required">*</span>') . '</p>' .
                '<p class="col"><label for="post_title" class="required"><span>*</span> ' . __('Title:') . '</label>' .
                form::field('post_title', 20, 255, [
                    'class'      => 'maximal',
                    'extra_html' => 'required placeholder="' . __('Title') . '"',
                ]) .
                '</p>' .
                '<div class="area"><label class="required" ' .
                'for="post_content"><span>*</span> ' . __('Content:') . '</label> ' .
                form::textarea('post_content', 50, 10, ['extra_html' => 'required placeholder="' . __('Content') . '"']) .
                '</div>' .
                '<p><label for="cat_id" class="classic">' . __('Category:') . '</label> ' .
                form::combo('cat_id', $categories_combo) . '</p>' .
                (App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_CATEGORIES,
                ]), App::blog()->id())
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
                '<p class="form-buttons"><input type="submit" value="' . __('Save') . '" name="save"> ' .
                (App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_PUBLISH,
                ]), App::blog()->id())
                    ? '<input type="hidden" value="' . __('Save and publish') . '" name="save-publish">'
                    : '') .
                App::nonce()->getFormNonce() .
                form::hidden('post_status', App::blog()::POST_PENDING) .
                form::hidden('post_format', App::auth()->getOption('post_format')) .
                form::hidden('post_excerpt', '') .
                form::hidden('post_lang', App::auth()->getInfo('user_lang')) .
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
