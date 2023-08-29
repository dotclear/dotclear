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
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use Dotclear\Plugin\widgets\Widgets as dcWidgets;
use Dotclear\Plugin\widgets\WidgetsStack;

class Widgets
{
    /**
     * Initializes the pages widget.
     *
     * @param      WidgetsStack  $widgets  The widgets
     */
    public static function initWidgets(WidgetsStack $widgets): void
    {
        $widgets
            ->create('pages', My::name(), FrontendTemplate::pagesWidget(...), null, 'List of published pages')
            ->addTitle(My::name())
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
        $default_widgets[dcWidgets::WIDGETS_NAV]->append($widgets->pages);
    }
}
