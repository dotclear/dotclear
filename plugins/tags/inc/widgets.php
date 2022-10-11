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
class tagsWidgets
{
    /**
     * Add the tags widget to the list of available widgets.
     *
     * @param      dcWidgets  $widgets  The widgets
     */
    public static function initWidgets(dcWidgets $widgets)
    {
        $widgets
            ->create('tags', __('Tags'), ['tplTags', 'tagsWidget'], null, 'Tags cloud')
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
     * @param      dcWidgets  $widgets          The widgets
     * @param      array      $default_widgets  The default widgets
     */
    public static function initDefaultWidgets(dcWidgets $widgets, array $default_widgets): void
    {
        $default_widgets['nav']->append($widgets->tags);
    }
}
