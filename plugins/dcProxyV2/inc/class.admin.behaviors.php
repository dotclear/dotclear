<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
use Dotclear\App;

/**
 * @brief   The module backend behaviors aliases handler.
 * @ingroup dcProxyV2
 */
class dcProxyV2AdminBehaviors
{
    // Count : 55

    public static function adminBlogFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminBlogFilter', dcCore::app(), $filters);
    }
    public static function adminBlogListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminBlogListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminBlogListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminBlogListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminBlogPreferencesForm(mixed $blog_settings): mixed
    {
        return App::behavior()->callBehavior('adminBlogPreferencesForm', dcCore::app(), $blog_settings);
    }
    public static function adminBlogsActionsPage(mixed $that): mixed
    {
        return App::behavior()->callBehavior('adminBlogsActionsPage', dcCore::app(), $that);
    }
    public static function adminColumnsLists(mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminColumnsLists', dcCore::app(), $cols);
    }
    public static function adminCommentFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminCommentFilter', dcCore::app(), $filters);
    }
    public static function adminCommentListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminCommentListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminCommentListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminCommentListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminCommentsActions(mixed $getRS, mixed $getAction, mixed $getRedirection): mixed
    {
        return App::behavior()->callBehavior('adminCommentsActions', dcCore::app(), $getRS, $getAction, $getRedirection);
    }
    public static function adminCommentsActionsPage(mixed $that): mixed
    {
        return App::behavior()->callBehavior('adminCommentsActionsPage', dcCore::app(), $that);
    }
    public static function adminCommentsSpamForm(): mixed
    {
        return App::behavior()->callBehavior('adminCommentsSpamForm', dcCore::app());
    }
    public static function adminCurrentThemeDetails(mixed $id, mixed $define): mixed
    {
        return App::behavior()->callBehavior('adminCurrentThemeDetails', dcCore::app(), $id, $define->dump());
    }
    public static function adminDashboardContents(mixed $__dashboard_contents): mixed
    {
        return App::behavior()->callBehavior('adminDashboardContents', dcCore::app(), $__dashboard_contents);
    }
    public static function adminDashboardFavorites(mixed $favorites): mixed
    {
        return App::behavior()->callBehavior('adminDashboardFavorites', dcCore::app(), $favorites);
    }
    public static function adminDashboardFavs(mixed $f): mixed
    {
        return App::behavior()->callBehavior('adminDashboardFavs', dcCore::app(), $f);
    }
    public static function adminDashboardFavsIcon(mixed $k, mixed $icons): mixed
    {
        return App::behavior()->callBehavior('adminDashboardFavsIcon', dcCore::app(), $k, $icons);
    }
    public static function adminDashboardItems(mixed $__dashboard_items): mixed
    {
        return App::behavior()->callBehavior('adminDashboardItems', dcCore::app(), $__dashboard_items);
    }
    public static function adminDashboardOptionsForm(): mixed
    {
        return App::behavior()->callBehavior('adminDashboardOptionsForm', dcCore::app());
    }
    public static function adminFiltersLists(mixed $sorts): mixed
    {
        return App::behavior()->callBehavior('adminFiltersLists', dcCore::app(), $sorts);
    }
    public static function adminMediaFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminMediaFilter', dcCore::app(), $filters);
    }
    public static function adminModulesListGetActions(mixed $list, mixed $define): mixed
    {
        return App::behavior()->callBehavior('adminModulesListGetActions', $list, $define->getId(), $define->dump());
    }
    public static function adminPageFooter(mixed $text): mixed
    {
        return App::behavior()->callBehavior('adminPageFooter', dcCore::app(), $text);
    }
    public static function adminPagesActionsPage(mixed $that): mixed
    {
        return App::behavior()->callBehavior('adminPagesActionsPage', dcCore::app(), $that);
    }
    public static function adminPagesListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPagesListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPagesListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPagesListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminPostFilter', dcCore::app(), $filters);
    }
    public static function adminPostListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPostListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPostListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPostListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostMiniListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPostMiniListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminPostMiniListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPostMiniListValue', dcCore::app(), $rs, $cols);
    }
    public static function adminPostsActions(mixed $getRS, mixed $getAction, mixed $getRedirection): mixed
    {
        return App::behavior()->callBehavior('adminPostsActions', dcCore::app(), $getRS, $getAction, $getRedirection);
    }
    public static function adminPostsActionsPage(mixed $that): mixed
    {
        return App::behavior()->callBehavior('adminPostsActionsPage', dcCore::app(), $that);
    }
    public static function adminPreferencesForm(): mixed
    {
        return App::behavior()->callBehavior('adminPreferencesForm', dcCore::app());
    }
    public static function adminRteFlags(mixed $rte): mixed
    {
        return App::behavior()->callBehavior('adminRteFlags', dcCore::app(), $rte);
    }
    public static function adminSearchPageCombo(mixed $table): mixed
    {
        return App::behavior()->callBehavior('adminSearchPageCombo', dcCore::app(), $table);
    }
    public static function adminSearchPageDisplay(mixed $args): mixed
    {
        return App::behavior()->callBehavior('adminSearchPageDisplay', dcCore::app(), $args);
    }
    public static function adminSearchPageHead(mixed $args): mixed
    {
        return App::behavior()->callBehavior('adminSearchPageHead', dcCore::app(), $args);
    }
    public static function adminSearchPageProcess(mixed $args): mixed
    {
        return App::behavior()->callBehavior('adminSearchPageProcess', dcCore::app(), $args);
    }
    public static function adminUsersActions(mixed $users, mixed $blogs, mixed $action, mixed $redir): mixed
    {
        return App::behavior()->callBehavior('adminUsersActions', dcCore::app(), $users, $blogs, $action, $redir);
    }
    public static function adminUsersActionsContent(mixed $action, mixed $hidden_fields): mixed
    {
        return App::behavior()->callBehavior('adminUsersActionsContent', dcCore::app(), $action, $hidden_fields);
    }
    public static function adminUserFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminUserFilter', dcCore::app(), $filters);
    }
    public static function adminUserListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminUserListHeader', dcCore::app(), $rs, $cols);
    }
    public static function adminUserListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminUserListValue', dcCore::app(), $rs, $cols);
    }

    public static function exportFull(mixed $exp): mixed
    {
        return App::behavior()->callBehavior('exportFull', dcCore::app(), $exp);
    }
    public static function exportSingle(mixed $exp, mixed $blog_id): mixed
    {
        return App::behavior()->callBehavior('exportSingle', dcCore::app(), $exp, $blog_id);
    }

    public static function importExportModules(mixed $modules): mixed
    {
        return App::behavior()->callBehavior('importExportModules', $modules, dcCore::app());
    }
    public static function importFull(mixed $line, mixed $that): mixed
    {
        return App::behavior()->callBehavior('importFull', $line, $that, dcCore::app());
    }
    public static function importInit(mixed $that): mixed
    {
        return App::behavior()->callBehavior('importInit', $that, dcCore::app());
    }
    public static function importPrepareDC12(mixed $line, mixed $that): mixed
    {
        return App::behavior()->callBehavior('importPrepareDC12', $line, $that, dcCore::app());
    }
    public static function importSingle(mixed $line, mixed $that): mixed
    {
        return App::behavior()->callBehavior('importSingle', $line, $that, dcCore::app());
    }

    public static function pluginsToolsHeaders(bool $config = false): mixed
    {
        return App::behavior()->callBehavior('pluginsToolsHeaders', dcCore::app(), $config);
    }
    public static function pluginsToolsTabs(): mixed
    {
        return App::behavior()->callBehavior('pluginsToolsTabs', dcCore::app());
    }
    public static function pluginBeforeDelete(mixed $define): mixed
    {
        return App::behavior()->callBehavior('pluginBeforeDelete', $define->dump());
    }
    public static function pluginAfterDelete(mixed $define): mixed
    {
        return App::behavior()->callBehavior('pluginAfterDelete', $define->dump());
    }
    public static function pluginBeforeAdd(mixed $define): mixed
    {
        return App::behavior()->callBehavior('pluginBeforeAdd', $define->dump());
    }
    public static function pluginAfterAdd(mixed $define): mixed
    {
        return App::behavior()->callBehavior('pluginAfterAdd', $define->dump());
    }
    public static function pluginBeforeDeactivate(mixed $define): mixed
    {
        return App::behavior()->callBehavior('pluginBeforeDeactivate', $define->dump());
    }
    public static function pluginAfterDeactivate(mixed $define): mixed
    {
        return App::behavior()->callBehavior('pluginAfterDeactivate', $define->dump());
    }
    public static function pluginBeforeUpdate(mixed $define): mixed
    {
        return App::behavior()->callBehavior('pluginBeforeUpdate', $define->dump());
    }
    public static function pluginAfterUpdate(mixed $define): mixed
    {
        return App::behavior()->callBehavior('pluginAfterUpdate', $define->dump());
    }

    public static function restCheckStoreUpdate(mixed $store, mixed $mod, mixed $url): mixed
    {
        return App::behavior()->callBehavior('restCheckStoreUpdate', dcCore::app(), $store, $mod, $url);
    }

    public static function themesToolsHeaders(bool $config = false): mixed
    {
        return App::behavior()->callBehavior('themesToolsHeaders', dcCore::app(), $config);
    }
    public static function themesToolsTabs(): mixed
    {
        return App::behavior()->callBehavior('themesToolsTabs', dcCore::app());
    }
    public static function themeBeforeDeactivate(mixed $define): mixed
    {
        return App::behavior()->callBehavior('themeBeforeDeactivate', $define->dump());
    }
    public static function themeAfterDeactivate(mixed $define): mixed
    {
        return App::behavior()->callBehavior('themeAfterDeactivate', $define->dump());
    }
    public static function themeBeforeDelete(mixed $define): mixed
    {
        return App::behavior()->callBehavior('themeBeforeDelete', $define->dump());
    }
    public static function themeAfterDelete(mixed $define): mixed
    {
        return App::behavior()->callBehavior('themeAfterDelete', $define->dump());
    }
    public static function themeBeforeAdd(mixed $define): mixed
    {
        return App::behavior()->callBehavior('themeBeforeAdd', $define->dump());
    }
    public static function themeAfterAdd(mixed $define): mixed
    {
        return App::behavior()->callBehavior('themeAfterAdd', $define->dump());
    }
    public static function themeBeforeUpdate(mixed $define): mixed
    {
        return App::behavior()->callBehavior('themeBeforeUpdate', $define->dump());
    }
    public static function themeAfterUpdate(mixed $define): mixed
    {
        return App::behavior()->callBehavior('themeAfterUpdate', $define->dump());
    }
}
