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
if (!defined('DC_RC_PATH')) {
    return;
}

$core->addBehavior('initWidgets', ['tagsWidgets', 'initWidgets']);
$core->addBehavior('initDefaultWidgets', ['tagsWidgets', 'initDefaultWidgets']);

class tagsWidgets
{
    public static function initWidgets($w)
    {
        $w
            ->create('tags', __('Tags'), ['tplTags', 'tagsWidget'], null, 'Tags cloud')
            ->addTitle(__('Menu'))
            ->setting('limit', __('Limit (empty means no limit):'), '20')
            ->setting('sortby', __('Order by:'), 'meta_id_lower', 'combo',
                [
                    __('Tag name')      => 'meta_id_lower',
                    __('Entries count') => 'count',
                    __('Newest entry')  => 'latest',
                    __('Oldest entry')  => 'oldest'
                ])
            ->setting('orderby', __('Sort:'), 'asc', 'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc'
                ])
            ->setting('alltagslinktitle', __('Link to all tags:'), __('All tags'))
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function initDefaultWidgets($w, $d)
    {
        $d['nav']->append($w->tags);
    }
}
