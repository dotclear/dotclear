<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\Plugin\widgets\Widgets as dcWidgets;
use Dotclear\Plugin\widgets\WidgetsStack;

class Widgets
{
    /**
     * Add the tags widget to the list of available widgets.
     *
     * @param      WidgetsStack  $widgets  The widgets
     */
    public static function initWidgets(WidgetsStack $widgets)
    {
        $widgets
            ->create('tags', My::name(), [FrontendTemplate::class, 'tagsWidget'], null, 'Tags cloud')
            ->addTitle(__('Menu'))
            ->setting('limit', __('Limit (empty means no limit):'), '20')
            ->setting(
                'sortby',
                __('Order by:'),
                'meta_id_lower',
                'combo',
                [
                    __('Tag name')      => 'meta_id_lower',
                    __('Entries count') => 'count',
                    __('Newest entry')  => 'latest',
                    __('Oldest entry')  => 'oldest',
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
            ->setting('alltagslinktitle', __('Link to all tags:'), __('All tags'))
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    /**
     * Add the tags widget to the default list of widgets
     *
     * @param      WidgetsStack  $widgets          The widgets
     * @param      array         $default_widgets  The default widgets
     */
    public static function initDefaultWidgets(WidgetsStack $widgets, array $default_widgets): void
    {
        $default_widgets[dcWidgets::WIDGETS_NAV]->append($widgets->tags);
    }
}
