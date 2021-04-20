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
if (!defined('DC_RC_PATH')) {
    return;
}

$core->addBehavior('initWidgets', ['pagesWidgets', 'initWidgets']);
$core->addBehavior('initDefaultWidgets', ['pagesWidgets', 'initDefaultWidgets']);

class pagesWidgets
{
    public static function initWidgets($w)
    {
        $w
            ->create('pages', __('Pages'), ['tplPages', 'pagesWidget'], null, 'List of published pages')
            ->addTitle(__('Pages'))
            ->setting('sortby', __('Order by:'), 'post_title', 'combo',
                [
                    __('Page title')       => 'post_title',
                    __('Page position')    => 'post_position',
                    __('Publication date') => 'post_dt'
                ])
            ->setting('orderby', __('Sort:'), 'asc', 'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc'
                ])
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function initDefaultWidgets($w, $d)
    {
        $d['nav']->append($w->pages);
    }
}
