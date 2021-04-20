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
if (!defined('DC_RC_PATH')) {
    return;
}

$core->addBehavior('initWidgets', ['blogrollWidgets', 'initWidgets']);
$core->addBehavior('initDefaultWidgets', ['blogrollWidgets', 'initDefaultWidgets']);

class blogrollWidgets
{
    public static function initWidgets($w)
    {
        $br         = new dcBlogroll($GLOBALS['core']->blog);
        $h          = $br->getLinksHierarchy($br->getLinks());
        $h          = array_keys($h);
        $categories = [__('All categories') => ''];
        foreach ($h as $v) {
            if ($v) {
                $categories[$v] = $v;
            }
        }
        unset($br, $h);

        $w
            ->create('links', __('Blogroll'), ['tplBlogroll', 'linksWidget'], null, 'Blogroll list')
            ->addTitle(__('Links'))
            ->setting('category', __('Category'), '', 'combo', $categories)
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function initDefaultWidgets($w, $d)
    {
        $d['extra']->append($w->links);
    }
}
