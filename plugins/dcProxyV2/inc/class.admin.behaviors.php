<?php

declare(strict_types=1);

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
use Dotclear\App;
use Dotclear\Module\ModuleDefine;

/**
 * @brief   The module backend behaviors aliases handler.
 * @ingroup dcProxyV2
 */
class dcProxyV2AdminBehaviors
{
    // Count : 55

    public static function adminBlogFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminBlogFilter', null, $filters);
    }

    public static function adminBlogListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminBlogListHeader', null, $rs, $cols);
    }

    public static function adminBlogListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminBlogListValue', null, $rs, $cols);
    }

    public static function adminBlogPreferencesForm(mixed $blog_settings): mixed
    {
        return App::behavior()->callBehavior('adminBlogPreferencesForm', null, $blog_settings);
    }

    public static function adminBlogsActionsPage(mixed $that): mixed
    {
        return App::behavior()->callBehavior('adminBlogsActionsPage', null, $that);
    }

    public static function adminColumnsLists(mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminColumnsLists', null, $cols);
    }

    public static function adminCommentFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminCommentFilter', null, $filters);
    }

    public static function adminCommentListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminCommentListHeader', null, $rs, $cols);
    }

    public static function adminCommentListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminCommentListValue', null, $rs, $cols);
    }

    public static function adminCommentsActions(mixed $getRS, mixed $getAction, mixed $getRedirection): mixed
    {
        return App::behavior()->callBehavior('adminCommentsActions', null, $getRS, $getAction, $getRedirection);
    }

    public static function adminCommentsActionsPage(mixed $that): mixed
    {
        return App::behavior()->callBehavior('adminCommentsActionsPage', null, $that);
    }

    public static function adminCommentsSpamForm(): mixed
    {
        return App::behavior()->callBehavior('adminCommentsSpamForm', null);
    }

    public static function adminCurrentThemeDetails(mixed $id, mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('adminCurrentThemeDetails', null, $id, $dump);
    }

    public static function adminDashboardContents(mixed $__dashboard_contents): mixed
    {
        return App::behavior()->callBehavior('adminDashboardContents', null, $__dashboard_contents);
    }

    public static function adminDashboardFavorites(mixed $favorites): mixed
    {
        return App::behavior()->callBehavior('adminDashboardFavorites', null, $favorites);
    }

    public static function adminDashboardFavs(mixed $f): mixed
    {
        return App::behavior()->callBehavior('adminDashboardFavs', null, $f);
    }

    public static function adminDashboardFavsIcon(mixed $k, mixed $icons): mixed
    {
        return App::behavior()->callBehavior('adminDashboardFavsIcon', null, $k, $icons);
    }

    public static function adminDashboardItems(mixed $__dashboard_items): mixed
    {
        return App::behavior()->callBehavior('adminDashboardItems', null, $__dashboard_items);
    }

    public static function adminDashboardOptionsForm(): mixed
    {
        return App::behavior()->callBehavior('adminDashboardOptionsForm', null);
    }

    public static function adminFiltersLists(mixed $sorts): mixed
    {
        return App::behavior()->callBehavior('adminFiltersLists', null, $sorts);
    }

    public static function adminMediaFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminMediaFilter', null, $filters);
    }

    public static function adminModulesListGetActions(mixed $list, mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;
        $id   = $define instanceof ModuleDefine ? $define->getId() : null;

        return App::behavior()->callBehavior('adminModulesListGetActions', $list, $id, $dump);
    }

    public static function adminPageFooter(mixed $text): mixed
    {
        return App::behavior()->callBehavior('adminPageFooter', null, $text);
    }

    public static function adminPagesActionsPage(mixed $that): mixed
    {
        return App::behavior()->callBehavior('adminPagesActionsPage', null, $that);
    }

    public static function adminPagesListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPagesListHeader', null, $rs, $cols);
    }

    public static function adminPagesListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPagesListValue', null, $rs, $cols);
    }

    public static function adminPostFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminPostFilter', null, $filters);
    }

    public static function adminPostListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPostListHeader', null, $rs, $cols);
    }

    public static function adminPostListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPostListValue', null, $rs, $cols);
    }

    public static function adminPostMiniListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPostMiniListHeader', null, $rs, $cols);
    }

    public static function adminPostMiniListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminPostMiniListValue', null, $rs, $cols);
    }

    public static function adminPostsActions(mixed $getRS, mixed $getAction, mixed $getRedirection): mixed
    {
        return App::behavior()->callBehavior('adminPostsActions', null, $getRS, $getAction, $getRedirection);
    }

    public static function adminPostsActionsPage(mixed $that): mixed
    {
        return App::behavior()->callBehavior('adminPostsActionsPage', null, $that);
    }

    public static function adminPreferencesForm(): mixed
    {
        return App::behavior()->callBehavior('adminPreferencesForm', null);
    }

    public static function adminRteFlags(mixed $rte): mixed
    {
        return App::behavior()->callBehavior('adminRteFlags', null, $rte);
    }

    public static function adminSearchPageCombo(mixed $table): mixed
    {
        return App::behavior()->callBehavior('adminSearchPageCombo', null, $table);
    }

    public static function adminSearchPageDisplay(mixed $args): mixed
    {
        return App::behavior()->callBehavior('adminSearchPageDisplay', null, $args);
    }

    public static function adminSearchPageHead(mixed $args): mixed
    {
        return App::behavior()->callBehavior('adminSearchPageHead', null, $args);
    }

    public static function adminSearchPageProcess(mixed $args): mixed
    {
        return App::behavior()->callBehavior('adminSearchPageProcess', null, $args);
    }

    public static function adminUsersActions(mixed $users, mixed $blogs, mixed $action, mixed $redir): mixed
    {
        return App::behavior()->callBehavior('adminUsersActions', null, $users, $blogs, $action, $redir);
    }

    public static function adminUsersActionsContent(mixed $action, mixed $hidden_fields): mixed
    {
        return App::behavior()->callBehavior('adminUsersActionsContent', null, $action, $hidden_fields);
    }

    public static function adminUserFilter(mixed $filters): mixed
    {
        return App::behavior()->callBehavior('adminUserFilter', null, $filters);
    }

    public static function adminUserListHeader(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminUserListHeader', null, $rs, $cols);
    }

    public static function adminUserListValue(mixed $rs, mixed $cols): mixed
    {
        return App::behavior()->callBehavior('adminUserListValue', null, $rs, $cols);
    }

    public static function exportFull(mixed $exp): mixed
    {
        return App::behavior()->callBehavior('exportFull', null, $exp);
    }

    public static function exportSingle(mixed $exp, mixed $blog_id): mixed
    {
        return App::behavior()->callBehavior('exportSingle', null, $exp, $blog_id);
    }

    public static function importExportModules(mixed $modules): mixed
    {
        return App::behavior()->callBehavior('importExportModules', $modules, null);
    }

    public static function importFull(mixed $line, mixed $that): mixed
    {
        return App::behavior()->callBehavior('importFull', $line, $that, null);
    }

    public static function importInit(mixed $that): mixed
    {
        return App::behavior()->callBehavior('importInit', $that, null);
    }

    public static function importPrepareDC12(mixed $line, mixed $that): mixed
    {
        return App::behavior()->callBehavior('importPrepareDC12', $line, $that, null);
    }

    public static function importSingle(mixed $line, mixed $that): mixed
    {
        return App::behavior()->callBehavior('importSingle', $line, $that, null);
    }

    public static function pluginsToolsHeaders(bool $config = false): mixed
    {
        return App::behavior()->callBehavior('pluginsToolsHeaders', null, $config);
    }

    public static function pluginsToolsTabs(): mixed
    {
        return App::behavior()->callBehavior('pluginsToolsTabs', null);
    }

    public static function pluginBeforeDelete(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('pluginBeforeDelete', $dump);
    }

    public static function pluginAfterDelete(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('pluginAfterDelete', $dump);
    }

    public static function pluginBeforeAdd(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('pluginBeforeAdd', $dump);
    }

    public static function pluginAfterAdd(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('pluginAfterAdd', $dump);
    }

    public static function pluginBeforeDeactivate(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('pluginBeforeDeactivate', $dump);
    }

    public static function pluginAfterDeactivate(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('pluginAfterDeactivate', $dump);
    }

    public static function pluginBeforeUpdate(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('pluginBeforeUpdate', $dump);
    }

    public static function pluginAfterUpdate(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('pluginAfterUpdate', $dump);
    }

    public static function restCheckStoreUpdate(mixed $store, mixed $mod, mixed $url): mixed
    {
        return App::behavior()->callBehavior('restCheckStoreUpdate', null, $store, $mod, $url);
    }

    public static function themesToolsHeaders(bool $config = false): mixed
    {
        return App::behavior()->callBehavior('themesToolsHeaders', null, $config);
    }

    public static function themesToolsTabs(): mixed
    {
        return App::behavior()->callBehavior('themesToolsTabs', null);
    }

    public static function themeBeforeDeactivate(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('themeBeforeDeactivate', $dump);
    }

    public static function themeAfterDeactivate(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('themeAfterDeactivate', $dump);
    }

    public static function themeBeforeDelete(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('themeBeforeDelete', $dump);
    }

    public static function themeAfterDelete(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('themeAfterDelete', $dump);
    }

    public static function themeBeforeAdd(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('themeBeforeAdd', $dump);
    }

    public static function themeAfterAdd(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('themeAfterAdd', $dump);
    }

    public static function themeBeforeUpdate(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('themeBeforeUpdate', $dump);
    }

    public static function themeAfterUpdate(mixed $define): mixed
    {
        $dump = $define instanceof ModuleDefine ? $define->dump() : null;

        return App::behavior()->callBehavior('themeAfterUpdate', $dump);
    }
}
