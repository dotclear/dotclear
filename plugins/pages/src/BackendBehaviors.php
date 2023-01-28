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
class pagesDashboard
{
    /**
     * Update number of pages of dashboard page icon
     *
     * @param      ArrayObject  $icon   The icon
     */
    public static function pagesDashboardCB(ArrayObject $icon)
    {
        $params              = new ArrayObject();
        $params['post_type'] = 'page';
        $page_count          = dcCore::app()->blog->getPosts($params, true)->f(0);
        if ($page_count > 0) {
            $str_pages     = ($page_count > 1) ? __('%d pages') : __('%d page');
            $icon['title'] = sprintf($str_pages, $page_count);
        }
    }

    /**
     * Check if pages plugin is active
     *
     * @param      string       $request  The request
     * @param      array        $params   The parameters
     *
     * @return     bool
     */
    public static function pagesActiveCB(string $request, array $params): bool
    {
        return ($request == 'plugin.php') && isset($params['p']) && $params['p'] == 'pages' && !(isset($params['act']) && $params['act'] == 'page');
    }

    /**
     * Check if new page is active
     *
     * @param      string       $request  The request
     * @param      array        $params   The parameters
     *
     * @return     bool
     */
    public static function newPageActiveCB(string $request, array $params): bool
    {
        return ($request == 'plugin.php') && isset($params['p']) && $params['p'] == 'pages' && isset($params['act']) && $params['act'] == 'page';
    }
}
