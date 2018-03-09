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

if (!defined('DC_RC_PATH')) {return;}

$core->addBehavior('initWidgets', array('pagesWidgets', 'initWidgets'));
$core->addBehavior('initDefaultWidgets', array('pagesWidgets', 'initDefaultWidgets'));

class pagesWidgets
{
    public static function initWidgets($w)
    {
        $w->create('pages', __('Pages'), array('tplPages', 'pagesWidget'), null, 'List of published pages');
        $w->pages->setting('title', __('Title (optional)') . ' :', __('Pages'));
        $w->pages->setting('homeonly', __('Display on:'), 1, 'combo',
            array(
                __('All pages')           => 0,
                __('Home page only')      => 1,
                __('Except on home page') => 2
            )
        );
        $w->pages->setting('sortby', __('Order by:'), 'post_title', 'combo',
            array(
                __('Page title')       => 'post_title',
                __('Page position')    => 'post_position',
                __('Publication date') => 'post_dt'
            )
        );
        $w->pages->setting('orderby', __('Sort:'), 'asc', 'combo',
            array(__('Ascending') => 'asc', __('Descending') => 'desc')
        );
        $w->pages->setting('content_only', __('Content only'), 0, 'check');
        $w->pages->setting('class', __('CSS class:'), '');
        $w->pages->setting('offline', __('Offline'), 0, 'check');
    }

    public static function initDefaultWidgets($w, $d)
    {
        $d['nav']->append($w->pages);
    }
}
