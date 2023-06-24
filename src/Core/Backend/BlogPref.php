<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use dcCore;

class BlogPref
{
    /**
     * JS Popup helper for static home linked to an entry
     *
     * @param      string  $plugin_id  Plugin id (or admin URL)
     *
     * @return     string
     */
    public static function adminPopupPosts(string $plugin_id = ''): string
    {
        if (empty($plugin_id) || $plugin_id != 'admin.blog_pref') {
            return '';
        }

        return
        Page::jsJson('admin.blog_pref', [
            'base_url' => dcCore::app()->blog->url,
        ]) .
        Page::jsLoad('js/_blog_pref_popup_posts.js');
    }
}
