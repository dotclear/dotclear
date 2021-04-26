<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcAdminBlogPref
{
    /**
     * JS Popup helper for static home linked to an entry
     *
     * @param      string  $editor  The editor
     *
     * @return     mixed
     */
    public static function adminPopupPosts($editor = '')
    {
        if (empty($editor) || $editor != 'admin.blog_pref') {
            return;
        }

        $res = dcPage::jsJson('admin.blog_pref', [
            'base_url' => $GLOBALS['core']->blog->url
        ]) .
        dcPage::jsLoad('js/_blog_pref_popup_posts.js');

        return $res;
    }
}
