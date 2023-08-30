<?php
/**
 * @brief dcProxyV2, a plugin for Dotclear 2
 *
 * Admin behaviours
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\App;

class dcProxyV2AdminBehaviors
{
    // Count : 55

    public static function adminBlogFilter($filters)
    {
        return App::behavior()->callBehavior('adminBlogFilter', dcCore::app(), $filters);
    }
    public static function adminBlogListHeader($rs, $cols)
    {
        return App::behavior()->callBehavior('adminBlogListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminBlogListValue($rs, $cols)
    {
        return App::behavior()->callBehavior('adminBlogListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminBlogPreferencesForm($blog_settings)
    {
        return App::behavior()->callBehavior('adminBlogPreferencesForm', dcCore::app(), $blog_settings);
    }
    public static function adminBlogsActionsPage($that)
    {
        return App::behavior()->callBehavior('adminBlogsActionsPage', dcCore::app(), $that);
    }
    public static function adminColumnsLists($cols)
    {
        return App::behavior()->callBehavior('adminColumnsLists', dcCore::app(), $cols);
    }
    public static function adminCommentFilter($filters)
    {
        return App::behavior()->callBehavior('adminCommentFilter', dcCore::app(), $filters);
    }
    public static function adminCommentListHeader($rs, $cols)
    {
        return App::behavior()->callBehavior('adminCommentListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminCommentListValue($rs, $cols)
    {
        return App::behavior()->callBehavior('adminCommentListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminCommentsActions($getRS, $getAction, $getRedirection)
    {
        return App::behavior()->callBehavior('adminCommentsActions', dcCore::app(), $getRS, $getAction, $getRedirection);
    }
    public static function adminCommentsActionsPage($that)
    {
        return App::behavior()->callBehavior('adminCommentsActionsPage', dcCore::app(), $that);
    }
    public static function adminCommentsSpamForm()
    {
        return App::behavior()->callBehavior('adminCommentsSpamForm', dcCore::app());
    }
    public static function adminCurrentThemeDetails($id, $define)
    {
        return App::behavior()->callBehavior('adminCurrentThemeDetails', dcCore::app(), $id, $define->dump());
    }
    public static function adminDashboardContents($__dashboard_contents)
    {
        return App::behavior()->callBehavior('adminDashboardContents', dcCore::app(), $__dashboard_contents);
    }
    public static function adminDashboardFavorites($favorites)
    {
        return App::behavior()->callBehavior('adminDashboardFavorites', dcCore::app(), $favorites);
    }
    public static function adminDashboardFavs($f)
    {
        return App::behavior()->callBehavior('adminDashboardFavs', dcCore::app(), $f);
    }
    public static function adminDashboardFavsIcon($k, $icons)
    {
        return App::behavior()->callBehavior('adminDashboardFavsIcon', dcCore::app(), $k, $icons);
    }
    public static function adminDashboardItems($__dashboard_items)
    {
        return App::behavior()->callBehavior('adminDashboardItems', dcCore::app(), $__dashboard_items);
    }
    public static function adminDashboardOptionsForm()
    {
        return App::behavior()->callBehavior('adminDashboardOptionsForm', dcCore::app());
    }
    public static function adminFiltersLists($sorts)
    {
        return App::behavior()->callBehavior('adminFiltersLists', dcCore::app(), $sorts);
    }
    public static function adminMediaFilter($filters)
    {
        return App::behavior()->callBehavior('adminMediaFilter', dcCore::app(), $filters);
    }
    public static function adminModulesListGetActions($list, $define)
    {
        return App::behavior()->callBehavior('adminModulesListGetActions', $list, $define->getId(), $define->dump());
    }
    public static function adminPageFooter($text)
    {
        return App::behavior()->callBehavior('adminPageFooter', dcCore::app(), $text);
    }
    public static function adminPagesActionsPage($that)
    {
        return App::behavior()->callBehavior('adminPagesActionsPage', dcCore::app(), $that);
    }
    public static function adminPagesListHeader($rs, $cols)
    {
        return App::behavior()->callBehavior('adminPagesListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPagesListValue($rs, $cols)
    {
        return App::behavior()->callBehavior('adminPagesListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostFilter($filters)
    {
        return App::behavior()->callBehavior('adminPostFilter', dcCore::app(), $filters);
    }
    public static function adminPostListHeader($rs, $cols)
    {
        return App::behavior()->callBehavior('adminPostListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPostListValue($rs, $cols)
    {
        return App::behavior()->callBehavior('adminPostListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostMiniListHeader($rs, $cols)
    {
        return App::behavior()->callBehavior('adminPostMiniListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPostMiniListValue($rs, $cols)
    {
        return App::behavior()->callBehavior('adminPostMiniListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostsActions($getRS, $getAction, $getRedirection)
    {
        return App::behavior()->callBehavior('adminPostsActions', dcCore::app(), $getRS, $getAction, $getRedirection);
    }
    public static function adminPostsActionsPage($that)
    {
        return App::behavior()->callBehavior('adminPostsActionsPage', dcCore::app(), $that);
    }
    public static function adminPreferencesForm()
    {
        return App::behavior()->callBehavior('adminPreferencesForm', dcCore::app());
    }
    public static function adminRteFlags($rte)
    {
        return App::behavior()->callBehavior('adminRteFlags', dcCore::app(), $rte);
    }
    public static function adminSearchPageCombo($table)
    {
        return App::behavior()->callBehavior('adminSearchPageCombo', dcCore::app(), $table);
    }
    public static function adminSearchPageDisplay($args)
    {
        return App::behavior()->callBehavior('adminSearchPageDisplay', dcCore::app(), $args);
    }
    public static function adminSearchPageHead($args)
    {
        return App::behavior()->callBehavior('adminSearchPageHead', dcCore::app(), $args);
    }
    public static function adminSearchPageProcess($args)
    {
        return App::behavior()->callBehavior('adminSearchPageProcess', dcCore::app(), $args);
    }
    public static function adminUsersActions($users, $blogs, $action, $redir)
    {
        return App::behavior()->callBehavior('adminUsersActions', dcCore::app(), $users, $blogs, $action, $redir);
    }
    public static function adminUsersActionsContent($action, $hidden_fields)
    {
        return App::behavior()->callBehavior('adminUsersActionsContent', dcCore::app(), $action, $hidden_fields);
    }
    public static function adminUserFilter($filters)
    {
        return App::behavior()->callBehavior('adminUserFilter', dcCore::app(), $filters);
    }
    public static function adminUserListHeader($rs, $cols)
    {
        return App::behavior()->callBehavior('adminUserListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminUserListValue($rs, $cols)
    {
        return App::behavior()->callBehavior('adminUserListValue', dcCore::app(), $rs, $cols);
    }

    public static function exportFull($exp)
    {
        return App::behavior()->callBehavior('exportFull', dcCore::app(), $exp);
    }
    public static function exportSingle($exp, $blog_id)
    {
        return App::behavior()->callBehavior('exportSingle', dcCore::app(), $exp, $blog_id);
    }

    public static function importExportModules($modules)
    {
        return App::behavior()->callBehavior('importExportModules', $modules, dcCore::app());
    }
    public static function importFull($line, $that)
    {
        return App::behavior()->callBehavior('importFull', $line, $that, dcCore::app());
    }
    public static function importInit($that)
    {
        return App::behavior()->callBehavior('importInit', $that, dcCore::app());
    }
    public static function importPrepareDC12($line, $that)
    {
        return App::behavior()->callBehavior('importPrepareDC12', $line, $that, dcCore::app());
    }
    public static function importSingle($line, $that)
    {
        return App::behavior()->callBehavior('importSingle', $line, $that, dcCore::app());
    }

    public static function pluginsToolsHeaders($config = false)
    {
        return App::behavior()->callBehavior('pluginsToolsHeaders', dcCore::app(), $config);
    }
    public static function pluginsToolsTabs()
    {
        return App::behavior()->callBehavior('pluginsToolsTabs', dcCore::app());
    }
    public static function pluginBeforeDelete($define)
    {
        return App::behavior()->callBehavior('pluginBeforeDelete', $define->dump());
    }
    public static function pluginAfterDelete($define)
    {
        return App::behavior()->callBehavior('pluginAfterDelete', $define->dump());
    }
    public static function pluginBeforeAdd($define)
    {
        return App::behavior()->callBehavior('pluginBeforeAdd', $define->dump());
    }
    public static function pluginAfterAdd($define)
    {
        return App::behavior()->callBehavior('pluginAfterAdd', $define->dump());
    }
    public static function pluginBeforeDeactivate($define)
    {
        return App::behavior()->callBehavior('pluginBeforeDeactivate', $define->dump());
    }
    public static function pluginAfterDeactivate($define)
    {
        return App::behavior()->callBehavior('pluginAfterDeactivate', $define->dump());
    }
    public static function pluginBeforeUpdate($define)
    {
        return App::behavior()->callBehavior('pluginBeforeUpdate', $define->dump());
    }
    public static function pluginAfterUpdate($define)
    {
        return App::behavior()->callBehavior('pluginAfterUpdate', $define->dump());
    }

    public static function restCheckStoreUpdate($store, $mod, $url)
    {
        return App::behavior()->callBehavior('restCheckStoreUpdate', dcCore::app(), $store, $mod, $url);
    }

    public static function themesToolsHeaders($config = false)
    {
        return App::behavior()->callBehavior('themesToolsHeaders', dcCore::app(), $config);
    }
    public static function themesToolsTabs()
    {
        return App::behavior()->callBehavior('themesToolsTabs', dcCore::app());
    }
    public static function themeBeforeDeactivate($define)
    {
        return App::behavior()->callBehavior('themeBeforeDeactivate', $define->dump());
    }
    public static function themeAfterDeactivate($define)
    {
        return App::behavior()->callBehavior('themeAfterDeactivate', $define->dump());
    }
    public static function themeBeforeDelete($define)
    {
        return App::behavior()->callBehavior('themeBeforeDelete', $define->dump());
    }
    public static function themeAfterDelete($define)
    {
        return App::behavior()->callBehavior('themeAfterDelete', $define->dump());
    }
    public static function themeBeforeAdd($define)
    {
        return App::behavior()->callBehavior('themeBeforeAdd', $define->dump());
    }
    public static function themeAfterAdd($define)
    {
        return App::behavior()->callBehavior('themeAfterAdd', $define->dump());
    }
    public static function themeBeforeUpdate($define)
    {
        return App::behavior()->callBehavior('themeBeforeUpdate', $define->dump());
    }
    public static function themeAfterUpdate($define)
    {
        return App::behavior()->callBehavior('themeAfterUpdate', $define->dump());
    }
}
