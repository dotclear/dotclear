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

if (!defined('DC_RC_PATH')) {return;}

$core->addBehavior('initWidgets', array('tagsWidgets', 'initWidgets'));
$core->addBehavior('initDefaultWidgets', array('tagsWidgets', 'initDefaultWidgets'));

class tagsWidgets
{
    public static function initWidgets($w)
    {
        $combo = array(
            __('Tag name')       => 'meta_id_lower',
            __('Entries count')  => 'count',
            __('Newest entry')   => 'latest',
            __('Oldest entry')   => 'oldest'
        );

        $w->create('tags', __('Tags'), array('tplTags', 'tagsWidget'), null, 'Tags cloud');
        $w->tags->setting('title', __('Title (optional)') . ' :', __('Tags'));
        $w->tags->setting('limit', __('Limit (empty means no limit):'), '20');
        $w->tags->setting('sortby', __('Order by:'), 'meta_id_lower', 'combo', $combo);
        $w->tags->setting('orderby', __('Sort:'), 'asc', 'combo',
            array(__('Ascending') => 'asc', __('Descending') => 'desc')
        );
        $w->tags->setting('alltagslinktitle', __('Link to all tags:'), __('All tags'));
        $w->tags->setting('homeonly', __('Display on:'), 0, 'combo',
            array(
                __('All pages')           => 0,
                __('Home page only')      => 1,
                __('Except on home page') => 2
            )
        );
        $w->tags->setting('content_only', __('Content only'), 0, 'check');
        $w->tags->setting('class', __('CSS class:'), '');
        $w->tags->setting('offline', __('Offline'), 0, 'check');
    }

    public static function initDefaultWidgets($w, $d)
    {
        $d['nav']->append($w->tags);
    }
}
