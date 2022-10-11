<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class blogrollWidgets
{
    /**
     * Initializes the blogroll widget.
     *
     * @param      dcWidgets  $widgets  The widgets
     */
    public static function initWidgets(dcWidgets $widgets): void
    {
        $blogroll  = new dcBlogroll(dcCore::app()->blog);
        $hierarchy = $blogroll->getLinksHierarchy($blogroll->getLinks());

        $hierarchy_cat    = array_keys($hierarchy);
        $categories_combo = [__('All categories') => ''];
        foreach ($hierarchy_cat as $category) {
            if ($category) {
                $categories_combo[$category] = $category;
            }
        }

        $widgets
            ->create('links', __('Blogroll'), [tplBlogroll::class, 'linksWidget'], null, 'Blogroll list')
            ->addTitle(__('Links'))
            ->setting('category', __('Category'), '', 'combo', $categories_combo)
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    /**
     * Add blogroll widget to default set
     *
     * @param      dcWidgets  $widgets          The widgets
     * @param      array      $default_widgets  The default widgets
     */
    public static function initDefaultWidgets(dcWidgets $widgets, array $default_widgets)
    {
        $default_widgets['extra']->append($widgets->links);
    }
}
