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
use Dotclear\Core\Core;

class dcProxyV2AdminBehaviors
{
    // Count : 55

    public static function adminBlogFilter($filters)
    {
        return Core::behavior()->callBehavior('adminBlogFilter', dcCore::app(), $filters);
    }
    public static function adminBlogListHeader($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminBlogListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminBlogListValue($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminBlogListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminBlogPreferencesForm($blog_settings)
    {
        return Core::behavior()->callBehavior('adminBlogPreferencesForm', dcCore::app(), $blog_settings);
    }
    public static function adminBlogsActionsPage($that)
    {
        return Core::behavior()->callBehavior('adminBlogsActionsPage', dcCore::app(), $that);
    }
    public static function adminColumnsLists($cols)
    {
        return Core::behavior()->callBehavior('adminColumnsLists', dcCore::app(), $cols);
    }
    public static function adminCommentFilter($filters)
    {
        return Core::behavior()->callBehavior('adminCommentFilter', dcCore::app(), $filters);
    }
    public static function adminCommentListHeader($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminCommentListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminCommentListValue($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminCommentListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminCommentsActions($getRS, $getAction, $getRedirection)
    {
        return Core::behavior()->callBehavior('adminCommentsActions', dcCore::app(), $getRS, $getAction, $getRedirection);
    }
    public static function adminCommentsActionsPage($that)
    {
        return Core::behavior()->callBehavior('adminCommentsActionsPage', dcCore::app(), $that);
    }
    public static function adminCommentsSpamForm()
    {
        return Core::behavior()->callBehavior('adminCommentsSpamForm', dcCore::app());
    }
    public static function adminCurrentThemeDetails($id, $define)
    {
        return Core::behavior()->callBehavior('adminCurrentThemeDetails', dcCore::app(), $id, $define->dump());
    }
    public static function adminDashboardContents($__dashboard_contents)
    {
        return Core::behavior()->callBehavior('adminDashboardContents', dcCore::app(), $__dashboard_contents);
    }
    public static function adminDashboardFavorites($favorites)
    {
        return Core::behavior()->callBehavior('adminDashboardFavorites', dcCore::app(), $favorites);
    }
    public static function adminDashboardFavs($f)
    {
        return Core::behavior()->callBehavior('adminDashboardFavs', dcCore::app(), $f);
    }
    public static function adminDashboardFavsIcon($k, $icons)
    {
        return Core::behavior()->callBehavior('adminDashboardFavsIcon', dcCore::app(), $k, $icons);
    }
    public static function adminDashboardItems($__dashboard_items)
    {
        return Core::behavior()->callBehavior('adminDashboardItems', dcCore::app(), $__dashboard_items);
    }
    public static function adminDashboardOptionsForm()
    {
        return Core::behavior()->callBehavior('adminDashboardOptionsForm', dcCore::app());
    }
    public static function adminFiltersLists($sorts)
    {
        return Core::behavior()->callBehavior('adminFiltersLists', dcCore::app(), $sorts);
    }
    public static function adminMediaFilter($filters)
    {
        return Core::behavior()->callBehavior('adminMediaFilter', dcCore::app(), $filters);
    }
    public static function adminModulesListGetActions($list, $define)
    {
        return Core::behavior()->callBehavior('adminModulesListGetActions', $list, $define->getId(), $define->dump());
    }
    public static function adminPageFooter($text)
    {
        return Core::behavior()->callBehavior('adminPageFooter', dcCore::app(), $text);
    }
    public static function adminPagesActionsPage($that)
    {
        return Core::behavior()->callBehavior('adminPagesActionsPage', dcCore::app(), $that);
    }
    public static function adminPagesListHeader($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminPagesListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPagesListValue($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminPagesListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostFilter($filters)
    {
        return Core::behavior()->callBehavior('adminPostFilter', dcCore::app(), $filters);
    }
    public static function adminPostListHeader($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminPostListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPostListValue($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminPostListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostMiniListHeader($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminPostMiniListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPostMiniListValue($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminPostMiniListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostsActions($getRS, $getAction, $getRedirection)
    {
        return Core::behavior()->callBehavior('adminPostsActions', dcCore::app(), $getRS, $getAction, $getRedirection);
    }
    public static function adminPostsActionsPage($that)
    {
        return Core::behavior()->callBehavior('adminPostsActionsPage', dcCore::app(), $that);
    }
    public static function adminPreferencesForm()
    {
        return Core::behavior()->callBehavior('adminPreferencesForm', dcCore::app());
    }
    public static function adminRteFlags($rte)
    {
        return Core::behavior()->callBehavior('adminRteFlags', dcCore::app(), $rte);
    }
    public static function adminSearchPageCombo($table)
    {
        return Core::behavior()->callBehavior('adminSearchPageCombo', dcCore::app(), $table);
    }
    public static function adminSearchPageDisplay($args)
    {
        return Core::behavior()->callBehavior('adminSearchPageDisplay', dcCore::app(), $args);
    }
    public static function adminSearchPageHead($args)
    {
        return Core::behavior()->callBehavior('adminSearchPageHead', dcCore::app(), $args);
    }
    public static function adminSearchPageProcess($args)
    {
        return Core::behavior()->callBehavior('adminSearchPageProcess', dcCore::app(), $args);
    }
    public static function adminUsersActions($users, $blogs, $action, $redir)
    {
        return Core::behavior()->callBehavior('adminUsersActions', dcCore::app(), $users, $blogs, $action, $redir);
    }
    public static function adminUsersActionsContent($action, $hidden_fields)
    {
        return Core::behavior()->callBehavior('adminUsersActionsContent', dcCore::app(), $action, $hidden_fields);
    }
    public static function adminUserFilter($filters)
    {
        return Core::behavior()->callBehavior('adminUserFilter', dcCore::app(), $filters);
    }
    public static function adminUserListHeader($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminUserListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminUserListValue($rs, $cols)
    {
        return Core::behavior()->callBehavior('adminUserListValue', dcCore::app(), $rs, $cols);
    }

    public static function exportFull($exp)
    {
        return Core::behavior()->callBehavior('exportFull', dcCore::app(), $exp);
    }
    public static function exportSingle($exp, $blog_id)
    {
        return Core::behavior()->callBehavior('exportSingle', dcCore::app(), $exp, $blog_id);
    }

    public static function importExportModules($modules)
    {
        return Core::behavior()->callBehavior('importExportModules', $modules, dcCore::app());
    }
    public static function importFull($line, $that)
    {
        return Core::behavior()->callBehavior('importFull', $line, $that, dcCore::app());
    }
    public static function importInit($that)
    {
        return Core::behavior()->callBehavior('importInit', $that, dcCore::app());
    }
    public static function importPrepareDC12($line, $that)
    {
        return Core::behavior()->callBehavior('importPrepareDC12', $line, $that, dcCore::app());
    }
    public static function importSingle($line, $that)
    {
        return Core::behavior()->callBehavior('importSingle', $line, $that, dcCore::app());
    }

    public static function pluginsToolsHeaders($config = false)
    {
        return Core::behavior()->callBehavior('pluginsToolsHeaders', dcCore::app(), $config);
    }
    public static function pluginsToolsTabs()
    {
        return Core::behavior()->callBehavior('pluginsToolsTabs', dcCore::app());
    }
    public static function pluginBeforeDelete($define)
    {
        return Core::behavior()->callBehavior('pluginBeforeDelete', $define->dump());
    }
    public static function pluginAfterDelete($define)
    {
        return Core::behavior()->callBehavior('pluginAfterDelete', $define->dump());
    }
    public static function pluginBeforeAdd($define)
    {
        return Core::behavior()->callBehavior('pluginBeforeAdd', $define->dump());
    }
    public static function pluginAfterAdd($define)
    {
        return Core::behavior()->callBehavior('pluginAfterAdd', $define->dump());
    }
    public static function pluginBeforeDeactivate($define)
    {
        return Core::behavior()->callBehavior('pluginBeforeDeactivate', $define->dump());
    }
    public static function pluginAfterDeactivate($define)
    {
        return Core::behavior()->callBehavior('pluginAfterDeactivate', $define->dump());
    }
    public static function pluginBeforeUpdate($define)
    {
        return Core::behavior()->callBehavior('pluginBeforeUpdate', $define->dump());
    }
    public static function pluginAfterUpdate($define)
    {
        return Core::behavior()->callBehavior('pluginAfterUpdate', $define->dump());
    }

    public static function restCheckStoreUpdate($store, $mod, $url)
    {
        return Core::behavior()->callBehavior('restCheckStoreUpdate', dcCore::app(), $store, $mod, $url);
    }

    public static function themesToolsHeaders($config = false)
    {
        return Core::behavior()->callBehavior('themesToolsHeaders', dcCore::app(), $config);
    }
    public static function themesToolsTabs()
    {
        return Core::behavior()->callBehavior('themesToolsTabs', dcCore::app());
    }
    public static function themeBeforeDeactivate($define)
    {
        return Core::behavior()->callBehavior('themeBeforeDeactivate', $define->dump());
    }
    public static function themeAfterDeactivate($define)
    {
        return Core::behavior()->callBehavior('themeAfterDeactivate', $define->dump());
    }
    public static function themeBeforeDelete($define)
    {
        return Core::behavior()->callBehavior('themeBeforeDelete', $define->dump());
    }
    public static function themeAfterDelete($define)
    {
        return Core::behavior()->callBehavior('themeAfterDelete', $define->dump());
    }
    public static function themeBeforeAdd($define)
    {
        return Core::behavior()->callBehavior('themeBeforeAdd', $define->dump());
    }
    public static function themeAfterAdd($define)
    {
        return Core::behavior()->callBehavior('themeAfterAdd', $define->dump());
    }
    public static function themeBeforeUpdate($define)
    {
        return Core::behavior()->callBehavior('themeBeforeUpdate', $define->dump());
    }
    public static function themeAfterUpdate($define)
    {
        return Core::behavior()->callBehavior('themeAfterUpdate', $define->dump());
    }
}
