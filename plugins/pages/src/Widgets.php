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

use Dotclear\Plugin\widgets\Widgets;
use Dotclear\Plugin\widgets\WidgetsStack;

class pagesWidgets
{
    /**
     * Initializes the pages widget.
     *
     * @param      WidgetsStack  $widgets  The widgets
     */
    public static function initWidgets(WidgetsStack $widgets): void
    {
        $widgets
            ->create('pages', __('Pages'), ['tplPages', 'pagesWidget'], null, 'List of published pages')
            ->addTitle(__('Pages'))
            ->setting(
                'sortby',
                __('Order by:'),
                'post_title',
                'combo',
                [
                    __('Page title')       => 'post_title',
                    __('Page position')    => 'post_position',
                    __('Publication date') => 'post_dt',
                ]
            )
            ->setting(
                'orderby',
                __('Sort:'),
                'asc',
                'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc',
                ]
            )
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    /**
     * Add pages widget to default set
     *
     * @param      WidgetsStack  $widgets          The widgets
     * @param      array         $default_widgets  The default widgets
     */
    public static function initDefaultWidgets(WidgetsStack $widgets, array $default_widgets): void
    {
        $default_widgets[Widgets::WIDGETS_NAV]->append($widgets->pages);
    }
}
