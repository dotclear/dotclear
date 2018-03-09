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

if (!defined('DC_RC_PATH')) {return;}

$core->addBehavior('initWidgets', array('blogrollWidgets', 'initWidgets'));
$core->addBehavior('initDefaultWidgets', array('blogrollWidgets', 'initDefaultWidgets'));

class blogrollWidgets
{
    public static function initWidgets($w)
    {
        $w->create('links', __('Blogroll'), array('tplBlogroll', 'linksWidget'), null, 'Blogroll list');
        $w->links->setting('title', __('Title (optional)') . ' :', __('Links'));

        $br         = new dcBlogroll($GLOBALS['core']->blog);
        $h          = $br->getLinksHierarchy($br->getLinks());
        $h          = array_keys($h);
        $categories = array(__('All categories') => '');
        foreach ($h as $v) {
            if ($v) {
                $categories[$v] = $v;
            }
        }
        unset($br, $h);
        $w->links->setting('category', __('Category'), '', 'combo', $categories);

        $w->links->setting('homeonly', __('Display on:'), 1, 'combo',
            array(
                __('All pages')           => 0,
                __('Home page only')      => 1,
                __('Except on home page') => 2
            )
        );
        $w->links->setting('content_only', __('Content only'), 0, 'check');
        $w->links->setting('class', __('CSS class:'), '');
        $w->links->setting('offline', __('Offline'), 0, 'check');
    }

    public static function initDefaultWidgets($w, $d)
    {
        $d['extra']->append($w->links);
    }
}
